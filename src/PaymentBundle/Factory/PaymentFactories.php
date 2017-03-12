<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Factory;

use CSBill\PaymentBundle\Exception\InvalidGatewayException;

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

    /**
     * @param array $factories
     */
    public function setGatewayFactories(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * @param array $gateForms
     */
    public function setGatewayForms(array $gateForms)
    {
        $this->forms = $gateForms;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getFactories($type = null)
    {
        if (null === $type) {
            return $this->factories;
        }

        return array_filter(
            $this->factories,
            function ($factory) use ($type) {
                return $type === $factory;
            }
        );
    }

    /**
     * @param string $gateway
     *
     * @return array
     */
    public function getForm($gateway)
    {
        return isset($this->forms[$gateway]) ? $this->forms[$gateway] : null;
    }

    /**
     * @param string $gateway
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getFactory($gateway)
    {
        if (isset($this->factories[$gateway])) {
            return $this->factories[$gateway];
        }

        throw new InvalidGatewayException($gateway);
    }

    /**
     * @param string $gateway
     *
     * @return bool
     */
    public function isOffline($gateway)
    {
        return isset($this->factories[$gateway]) && $this->factories[$gateway] === 'offline';
    }
}
