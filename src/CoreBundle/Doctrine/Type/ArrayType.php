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

namespace SolidInvoice\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Override;
use function is_resource;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function stream_get_contents;
use function unserialize;

/**
 * Re-implements the {@see \Doctrine\DBAL\Types\Type} "array" mapping type that was removed in DBAL 4.
 *
 * It is kept for backwards compatibility so that the historical migrations (which create columns with
 * the legacy serialize-backed "array" type) and the entities still mapping to it keep working.
 */
final class ArrayType extends Type
{
    public const string NAME = 'array';

    /**
     * @param array<string, mixed> $column
     */
    #[Override]
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    #[Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        return serialize($value);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): array
    {
        if ($value === null) {
            return [];
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        set_error_handler(static fn (): bool => true);

        try {
            // This type only ever stores serialized arrays, so objects are never
            // expected: disallow them to avoid PHP object-injection, and fall back
            // to an empty array for corrupt/legacy values that fail to unserialize.
            $value = unserialize((string) $value, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }

        return is_array($value) ? $value : [];
    }
}
