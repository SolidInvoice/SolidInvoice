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

namespace SolidInvoice\CoreBundle\Export;

use Brick\Math\BigNumber;
use Doctrine\ORM\Mapping\ClassMetadata;
use Money\Currencies\ISOCurrencies;
use Money\Money;
use SolidInvoice\CoreBundle\Export\Discovery\EntityExportSpec;
use SolidInvoice\CoreBundle\Export\Serializer\Normalizer\ExportMoneyNormalizer;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Converts a managed entity into a flat associative array suitable for any
 * export encoder (CSV / JSON / XML).
 *
 * Money values are flattened to `{field}_amount` + `{field}_currency`. Owning-side
 * single-valued associations are emitted as base58 ULIDs (so the output stays
 * relational and never recurses into related entities).
 */
final readonly class EntityRowNormalizer
{
    private ISOCurrencies $currencies;

    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->currencies = new ISOCurrencies();
    }

    /**
     * @param ClassMetadata<object> $metadata
     * @return array<string, mixed>
     */
    public function normalize(object $entity, ClassMetadata $metadata, EntityExportSpec $spec): array
    {
        $row = [];

        foreach ($spec->includedProperties as $property) {
            $rawValue = $this->readProperty($entity, $property);

            if ($metadata->hasAssociation($property)) {
                $row[$property] = $this->normalizeAssociation($rawValue);
                continue;
            }

            foreach ($this->normalizeScalar($property, $rawValue) as $key => $value) {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    private function readProperty(object $entity, string $property): mixed
    {
        try {
            return $this->propertyAccessor->getValue($entity, $property);
        } catch (NoSuchPropertyException) {
            return null;
        }
    }

    private function normalizeAssociation(mixed $related): ?string
    {
        if (! is_object($related)) {
            return null;
        }

        try {
            $id = $this->propertyAccessor->getValue($related, 'id');
        } catch (NoSuchPropertyException) {
            return null;
        }

        if ($id instanceof Ulid) {
            return $id->toBase58();
        }

        if (is_scalar($id)) {
            return (string) $id;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeScalar(string $field, mixed $value): array
    {
        if ($value instanceof Money) {
            return ValueFormatter::flattenMoney($field, $value, $this->currencies);
        }

        if ($value instanceof BigNumber) {
            return [$field => ExportMoneyNormalizer::amountToDecimalString($value, 2)];
        }

        return [$field => ValueFormatter::formatScalar($value)];
    }
}
