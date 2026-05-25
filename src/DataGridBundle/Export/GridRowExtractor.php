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

namespace SolidInvoice\DataGridBundle\Export;

use Brick\Math\BigNumber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportMoneyNormalizer;
use SolidInvoice\CoreBundle\Export\ValueFormatter;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\Ulid;
use function is_scalar;
use function str_contains;
use function strstr;

/**
 * Extracts raw, export-friendly values from entities using grid column definitions.
 *
 * Every row starts with the entity's primary identifier as `id` (base58 for ULIDs),
 * regardless of whether the grid exposes an `id` column — this guarantees exported
 * rows are uniquely addressable for downstream import/sync flows.
 *
 * Relation columns (backed by a Doctrine association) emit two keys per column:
 *   - `{field}`     the display label (column's formatValue output, or the related entity's __toString)
 *   - `{field}_id`  the related entity's ULID (base58)
 *
 * Money columns emit two keys per column:
 *   - `{field}_amount`    the decimal amount (major units)
 *   - `{field}_currency`  the ISO currency code
 *
 * All formats (CSV / JSON / XML) receive the same flat key shape for consistency.
 * @see \SolidInvoice\DataGridBundle\Tests\Export\GridRowExtractorTest
 */
final readonly class GridRowExtractor
{
    private ISOCurrencies $currencies;

    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private ManagerRegistry $registry,
    ) {
        $this->currencies = new ISOCurrencies();
    }

    /**
     * @param list<Column> $columns
     * @return array<string, scalar|null>
     */
    public function extract(array $columns, object $entity): array
    {
        $metadata = $this->metadataFor($entity::class);
        $row = ['id' => $this->extractEntityId($entity, $metadata)];

        foreach ($columns as $column) {
            foreach ($this->extractColumn($column, $entity, $metadata) as $key => $value) {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    /**
     * Returns the entity's primary identifier as a string. ULIDs are encoded as
     * base58 for compactness and consistency with relation `_id` fields.
     * @param ?ClassMetadata<object> $metadata
     */
    private function extractEntityId(object $entity, ?ClassMetadata $metadata): ?string
    {
        if ($metadata instanceof ClassMetadata) {
            $values = $metadata->getIdentifierValues($entity);
            if ($values !== []) {
                return $this->stringifyIdentifier(reset($values));
            }
        }

        try {
            $id = $this->propertyAccessor->getValue($entity, 'id');
        } catch (NoSuchPropertyException) {
            return null;
        }

        return $this->stringifyIdentifier($id);
    }

    private function stringifyIdentifier(mixed $id): ?string
    {
        if ($id instanceof Ulid) {
            return $id->toBase58();
        }

        if (is_scalar($id)) {
            return (string) $id;
        }

        return null;
    }

    /**
     * @return array<string, scalar|null>
     * @param ?ClassMetadata<object> $metadata
     */
    private function extractColumn(Column $column, object $entity, ?ClassMetadata $metadata): array
    {
        $field = $column->getField();
        $associationField = $this->associationField($field, $metadata);

        try {
            $raw = $this->propertyAccessor->getValue($entity, $field);
        } catch (NoSuchPropertyException) {
            $raw = $entity;
        }

        $value = $column->getFormatValue()($raw, $entity);
        $normalized = $this->normalizeValue($column, $field, $value);

        if ($associationField !== null) {
            $normalized[$associationField . '_id'] = $this->extractRelationId($entity, $associationField);
        }

        return $normalized;
    }

    private function extractRelationId(object $entity, string $associationField): ?string
    {
        try {
            $related = $this->propertyAccessor->getValue($entity, $associationField);
        } catch (NoSuchPropertyException) {
            return null;
        }

        if (! is_object($related)) {
            return null;
        }

        return $this->extractUlid($related);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function normalizeValue(Column $column, string $field, mixed $value): array
    {
        if ($value instanceof Money) {
            return ValueFormatter::flattenMoney($field, $value, $this->currencies);
        }

        if ($value instanceof BigNumber) {
            if ($column instanceof MoneyColumn) {
                return [
                    $field . '_amount' => ExportMoneyNormalizer::amountToDecimalString($value, 2),
                    $field . '_currency' => null,
                ];
            }

            // Non-MoneyColumn BigNumber falls through to its raw string form. Grids
            // that use BigNumber values typically attach a formatValue closure that
            // converts to Money, so this branch is rarely hit in practice.
            return [$field => $value->__toString()];
        }

        return [$field => ValueFormatter::formatScalar($value)];
    }

    private function extractUlid(object $related): ?string
    {
        try {
            $id = $this->propertyAccessor->getValue($related, 'id');
        } catch (NoSuchPropertyException) {
            return null;
        }

        return $this->stringifyIdentifier($id);
    }

    /**
     * Returns the association field name on the entity class, or null if the column
     * does not map to a Doctrine association.
     * @param ?ClassMetadata<object> $metadata
     */
    private function associationField(string $field, ?ClassMetadata $metadata): ?string
    {
        if (! $metadata instanceof ClassMetadata) {
            return null;
        }

        $rootField = str_contains($field, '.') ? strstr($field, '.', true) : $field;

        if ($rootField === false) {
            return null;
        }

        return $metadata->hasAssociation($rootField) ? $rootField : null;
    }

    /**
     * @param class-string $class
     * @return ?ClassMetadata<object>
     */
    private function metadataFor(string $class): ?ClassMetadata
    {
        $manager = $this->registry->getManagerForClass($class);

        if ($manager === null) {
            return null;
        }

        $metadata = $manager->getClassMetadata($class);
        assert($metadata instanceof ClassMetadata);

        return $metadata;
    }
}
