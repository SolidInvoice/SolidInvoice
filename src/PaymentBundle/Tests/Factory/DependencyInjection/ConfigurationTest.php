<?php

declare(strict_types=1);
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Tests\Factory\DependencyInjection;

use CSBill\PaymentBundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testValidConfig()
    {
        $this->assertConfigurationIsValid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'factory' => 'one',
                            'form' => 'two',
                        ],
                    ],
                ],
            ]
        );
    }

    public function testFormIsOptionalConfig()
    {
        $this->assertConfigurationIsValid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'factory' => 'one',
                        ],
                    ],
                ],
            ]
        );
    }

    public function testNoGatewaysConfigured()
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [],
                ],
            ],
            'The path "payment.gateways" should have at least 1 element(s) defined.'
        );
    }

    public function testFactoryConfigCannotBeEmpty()
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'factory' => '',
                        ],
                    ],
                ],
            ],
            'The path "payment.gateways.one.factory" cannot contain an empty value, but got "".'
        );
    }

    public function testFormConfigCannotBeEmpty()
    {
        $this->assertConfigurationIsInvalid(
            [
                'payment' => [
                    'gateways' => [
                        'one' => [
                            'factory' => 'one',
                            'form' => null,
                        ],
                    ],
                ],
            ],
            'The path "payment.gateways.one.form" cannot contain an empty value, but got null.'
        );
    }
}
