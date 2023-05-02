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

namespace SolidInvoice\ApiBundle\Serializer\Normalizer;

use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\MoneyNormalizerTest
 */
class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(private readonly MoneyFormatterInterface $formatter, private readonly SystemConfig $systemConfig)
    {
    }

    /**
     * @param string|int $data
     * @param array<string, mixed> $context
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): Money
    {
        // @TODO: Currency should be determined if there is a client added to the context
        return new Money($data * 100, $this->systemConfig->getCurrency());
    }

    /**
     * @param mixed $data
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return Money::class === $type || MoneyEntity::class === $type;
    }

    /**
     * @param Money $object
     * @param array<string, mixed> $context
     */
    public function normalize($object, string $format = null, array $context = []): string
    {
        return $this->formatter->format($object);
    }

    /**
     * @param mixed $data
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Money;
    }
}
