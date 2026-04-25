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

namespace SolidInvoice\CoreBundle\Export\Serializer\Normalizer;

use BackedEnum;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExportEnumNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): int|string
    {
        assert($object instanceof BackedEnum);

        return $object->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BackedEnum;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [BackedEnum::class => true];
    }
}
