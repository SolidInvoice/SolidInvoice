<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Export\Discovery;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use ReflectionClass;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Export\Attribute\ExportIgnore;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use function array_values;
use function class_uses;
use function in_array;
use function ksort;
use function strtolower;

/**
 * Discovers entities to include in a full company export by walking Doctrine metadata.
 *
 * Rules:
 *   - Entity must use the `CompanyAware` trait (recursively through parent classes
 *     and composed traits).
 *   - Child entities are picked up via OneToMany/ManyToMany associations on
 *     company-aware roots, as long as the target is not itself company-aware
 *     (which would make it its own root).
 *   - Entities or properties annotated with `#[ExportIgnore]` are skipped.
 *
 * The discovery list is stable (sorted by filename) so exports are reproducible.
 */
final class EntityDiscovery
{
    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @return list<EntityExportSpec>
     */
    public function discover(): array
    {
        $manager = $this->registry->getManager();
        assert($manager instanceof EntityManagerInterface);

        /** @var array<string, EntityExportSpec> $specs */
        $specs = [];

        foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            assert($metadata instanceof ClassMetadata);

            if ($this->skipMetadata($metadata)) {
                continue;
            }

            $reflection = new ReflectionClass($metadata->getName());

            if ($this->hasIgnoreAttribute($reflection)) {
                continue;
            }

            if (! $this->usesCompanyAware($reflection)) {
                continue;
            }

            $spec = $this->buildSpec($metadata, $reflection);
            $specs[$spec->filename] = $spec;
        }

        foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
            assert($metadata instanceof ClassMetadata);

            if ($this->skipMetadata($metadata)) {
                continue;
            }

            $reflection = new ReflectionClass($metadata->getName());

            if ($this->hasIgnoreAttribute($reflection)) {
                continue;
            }

            if ($this->usesCompanyAware($reflection)) {
                continue;
            }

            if (! $this->isChildOfCompanyAwareRoot($metadata)) {
                continue;
            }

            $spec = $this->buildSpec($metadata, $reflection);
            $specs[$spec->filename] = $spec;
        }

        ksort($specs);

        return array_values($specs);
    }

    /**
     * Skip classes that are not independently queryable entities: mapped superclasses
     * (no table) and abstract STI parents.
     *
     * @param ClassMetadata<object> $metadata
     */
    private function skipMetadata(ClassMetadata $metadata): bool
    {
        if ($metadata->isMappedSuperclass) {
            return true;
        }

        if ((new ReflectionClass($metadata->getName()))->isAbstract()) {
            return true;
        }

        return false;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function usesCompanyAware(ReflectionClass $reflection): bool
    {
        return $this->traitUsedRecursively($reflection, CompanyAware::class);
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function traitUsedRecursively(ReflectionClass $reflection, string $traitName): bool
    {
        $classes = [];
        $current = $reflection;
        while ($current !== false) {
            $classes[] = $current->getName();
            $current = $current->getParentClass();
        }

        foreach ($classes as $class) {
            /** @var array<string, string>|false $uses */
            $uses = class_uses($class);
            if ($uses === false) {
                continue;
            }

            if (in_array($traitName, $uses, true)) {
                return true;
            }

            foreach ($uses as $usedTrait) {
                $usedReflection = new ReflectionClass($usedTrait);
                if ($this->traitUsedRecursively($usedReflection, $traitName)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function hasIgnoreAttribute(ReflectionClass $reflection): bool
    {
        return $reflection->getAttributes(ExportIgnore::class) !== [];
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @param ReflectionClass<object> $reflection
     */
    private function buildSpec(ClassMetadata $metadata, ReflectionClass $reflection): EntityExportSpec
    {
        $included = [];

        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if (! $reflection->hasProperty((string) $fieldName)) {
                continue;
            }

            $property = $reflection->getProperty((string) $fieldName);
            if ($this->hasPropertyIgnoreAttribute($property)) {
                continue;
            }

            $included[] = (string) $fieldName;
        }

        foreach ($metadata->associationMappings as $assocName => $assocMapping) {
            if (! $reflection->hasProperty((string) $assocName)) {
                continue;
            }

            $property = $reflection->getProperty((string) $assocName);
            if ($this->hasPropertyIgnoreAttribute($property)) {
                continue;
            }

            // Only owning-side ToOne associations are included inline (as FK IDs).
            // ToMany collections are handled via their child entity's own export file.
            if ($metadata->isSingleValuedAssociation((string) $assocName)) {
                $included[] = (string) $assocName;
            }
        }

        $filename = $this->filenameFor($metadata);

        return new EntityExportSpec(
            entityClass: $metadata->getName(),
            filename: $filename,
            includedProperties: $included,
        );
    }

    private function hasPropertyIgnoreAttribute(ReflectionProperty $property): bool
    {
        return $property->getAttributes(ExportIgnore::class) !== [];
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function isChildOfCompanyAwareRoot(ClassMetadata $metadata): bool
    {
        $manager = $this->registry->getManagerForClass($metadata->getName());
        if (! $manager instanceof EntityManagerInterface) {
            return false;
        }

        foreach ($metadata->associationMappings as $assocName => $assocMapping) {
            if (! $metadata->isSingleValuedAssociation((string) $assocName)) {
                continue;
            }

            $targetClass = is_array($assocMapping)
                ? $assocMapping['targetEntity']
                : $assocMapping->targetEntity;

            if (! $manager->getMetadataFactory()->hasMetadataFor($targetClass)) {
                continue;
            }

            $targetReflection = new ReflectionClass($targetClass);
            if ($this->hasIgnoreAttribute($targetReflection)) {
                continue;
            }

            if ($this->usesCompanyAware($targetReflection)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ClassMetadata<object> $metadata
     */
    private function filenameFor(ClassMetadata $metadata): string
    {
        $tableName = $metadata->getTableName();

        return strtolower($tableName);
    }
}
