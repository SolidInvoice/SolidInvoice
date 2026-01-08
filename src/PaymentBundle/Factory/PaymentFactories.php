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

namespace SolidInvoice\PaymentBundle\Factory;

use SolidInvoice\PaymentBundle\Exception\InvalidGatewayException;

/**
 * @see \SolidInvoice\PaymentBundle\Tests\Factory\PaymentFactoriesTest
 */
class PaymentFactories
{
    /**
     * @var array<string, string>|null
     */
    private ?array $factories = null;

    /**
     * @var array<string, string>|null
     */
    private ?array $forms = null;

    /**
     * @param array<string, string> $factories
     */
    public function setGatewayFactories(array $factories): void
    {
        $this->factories = $factories;
    }

    /**
     * @param array<string, string> $gateForms
     */
    public function setGatewayForms(array $gateForms): void
    {
        $this->forms = $gateForms;
    }

    /**
     * @return array<string, string>|null
     */
    public function getFactories(?string $type = null): ?array
    {
        if (null === $type) {
            return $this->factories;
        }

        return array_filter(
            $this->factories,
            static fn ($factory): bool => $type === $factory
        );
    }

    public function getForm(string $gateway): ?string
    {
        return $this->forms[$gateway] ?? null;
    }

    /**
     * @throws InvalidGatewayException
     */
    public function getFactory(string $gateway): ?string
    {
        if (isset($this->factories[$gateway])) {
            return $this->factories[$gateway];
        }

        throw new InvalidGatewayException($gateway);
    }

    public function isOffline(string $gateway): bool
    {
        return (isset($this->factories[$gateway]) && 'offline' === $this->factories[$gateway]) || 'offline' === $gateway;
    }
}
