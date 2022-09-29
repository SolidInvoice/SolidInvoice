<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;

/**
 * @deprecated Only here for backwards compatibility to ensure migrations work.
 */
final class JsonArrayType extends JsonType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return [];
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        return json_decode($value, true);
    }

    public function getName(): string
    {
        return 'json_array';
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
