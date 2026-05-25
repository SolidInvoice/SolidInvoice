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
use function strtolower;
use function usort;

/**
 * Discovers entities to include in a full company export by walking Doctrine metadata.
 *
 * Rules:
 *   - Roots: entities that use the `CompanyAware` trait (recursively through parent
 *     classes and composed traits) and have a real table.
 *   - Children: non-CompanyAware entities that have an owning-side ToOne association
 *     pointing at a CompanyAware root (e.g. `Invoice\RecurringOptions.recurringInvoice`
 *     pointing at `RecurringInvoice`). The owning association name is recorded as
 *     `companyScopeAssociation` on the spec so the exporter can join through it and
 *     filter by the active company at query time — children have no `company_id`
 *     of their own and would otherwise leak across tenants.
 *   - Mapped superclasses, abstract STI parents, and entities/properties annotated
 *     `#[ExportIgnore]` are skipped.
 *
 * The discovery list is stable (sorted by filename) so exports are reproducible.
 */
final readonly class EntityDiscovery
{
    public function __construct(
        private ManagerRegistry $registry,
    ) {
    }

    /**
     * @return list<EntityExportSpec>
     */
    public function discover(): array
    {
        $manager = $this->registry->getManager();
        assert($manager instanceof EntityManagerInterface);

        /** @var array<class-string, EntityExportSpec> $specs */
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

            if ($this->usesCompanyAware($reflection)) {
                $specs[$metadata->getName()] = $this->buildSpec($metadata, $reflection, null);
                continue;
            }

            $companyScope = $this->companyScopeAssociation($metadata);
            if ($companyScope !== null) {
                $specs[$metadata->getName()] = $this->buildSpec($metadata, $reflection, $companyScope);
            }
        }

        // Stable order by filename for reproducible exports.
        usort($specs, static fn (EntityExportSpec $a, EntityExportSpec $b): int => $a->filename <=> $b->filename);

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

        return new ReflectionClass($metadata->getName())->isAbstract();
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
    private function buildSpec(
        ClassMetadata $metadata,
        ReflectionClass $reflection,
        ?string $companyScopeAssociation,
    ): EntityExportSpec {
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
            $name = (string) $assocName;

            if (! $reflection->hasProperty($name)) {
                continue;
            }

            $property = $reflection->getProperty($name);
            if ($this->hasPropertyIgnoreAttribute($property)) {
                continue;
            }

            // Only owning-side ToOne associations are included inline (as FK IDs).
            // ToMany collections are handled via their child entity's own export file,
            // and inverse-side ToOne associations have no FK column on this entity.
            if ($metadata->isSingleValuedAssociation($name) && $this->isOwningSide($assocMapping)) {
                $included[] = $name;
            }
        }

        return new EntityExportSpec(
            entityClass: $metadata->getName(),
            filename: $this->filenameFor($metadata),
            includedProperties: $included,
            companyScopeAssociation: $companyScopeAssociation,
        );
    }

    private function hasPropertyIgnoreAttribute(ReflectionProperty $property): bool
    {
        return $property->getAttributes(ExportIgnore::class) !== [];
    }

    /**
     * Doctrine ORM 3.x exposes association mappings as typed value objects with an
     * `isOwningSide` property; older array-shaped mappings used the same key. This
     * helper handles both representations.
     */
    private function isOwningSide(mixed $assocMapping): bool
    {
        if (is_array($assocMapping)) {
            return (bool) ($assocMapping['isOwningSide'] ?? false);
        }

        if (is_object($assocMapping) && property_exists($assocMapping, 'isOwningSide')) {
            return (bool) $assocMapping->isOwningSide;
        }

        return false;
    }

    /**
     * Returns the owning-side ToOne association on this entity that points at a
     * CompanyAware parent, or null if no such association exists. The returned
     * association name is the path the exporter joins through to scope queries
     * to the active company.
     *
     * @param ClassMetadata<object> $metadata
     */
    private function companyScopeAssociation(ClassMetadata $metadata): ?string
    {
        $manager = $this->registry->getManagerForClass($metadata->getName());
        if (! $manager instanceof EntityManagerInterface) {
            return null;
        }

        foreach ($metadata->associationMappings as $assocName => $assocMapping) {
            $name = (string) $assocName;

            if (! $metadata->isSingleValuedAssociation($name) || ! $this->isOwningSide($assocMapping)) {
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
                return $name;
            }
        }

        return null;
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
