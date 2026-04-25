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

use Money\Currency;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ExportCurrencyNormalizer implements NormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        assert($object instanceof Currency);

        return $object->getCode();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Currency;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [Currency::class => true];
    }
}
