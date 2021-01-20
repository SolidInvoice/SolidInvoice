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

use Exception;
use SolidInvoice\PaymentBundle\Exception\InvalidGatewayException;

class PaymentFactories
{
    /**
     * @var array
     */
    private $factories;

    /**
     * @var array
     */
    private $forms;

    public function setGatewayFactories(array $factories)
    {
        $this->factories = $factories;
    }

    public function setGatewayForms(array $gateForms)
    {
        $this->forms = $gateForms;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getFactories(string $type = null): ?array
    {
        if (null === $type) {
            return $this->factories;
        }

        return array_filter(
            $this->factories,
            function ($factory) use ($type): bool {
                return $type === $factory;
            }
        );
    }

    /**
     * @return string
     */
    public function getForm(string $gateway): ?string
    {
        return $this->forms[$gateway] ?? null;
    }

    /**
     * @return string
     *
     * @throws Exception
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
        return isset($this->factories[$gateway]) && 'offline' === $this->factories[$gateway];
    }
}
