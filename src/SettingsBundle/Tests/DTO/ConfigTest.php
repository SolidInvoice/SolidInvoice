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

namespace SolidInvoice\SettingsBundle\Tests\DTO;

use PHPUnit\Framework\TestCase;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @covers \SolidInvoice\SettingsBundle\DTO\Config
 */
final class ConfigTest extends TestCase
{
    public function testConfigWithoutFormOptions(): void
    {
        $config = new Config(
            'test/key',
            'test_value',
            'Test description',
            TextType::class
        );

        self::assertSame('test/key', $config->key);
        self::assertSame('test_value', $config->value);
        self::assertSame('Test description', $config->description);
        self::assertSame(TextType::class, $config->formType);
        self::assertSame([], $config->formOptions);
    }

    public function testConfigWithFormOptions(): void
    {
        $formOptions = ['trial_restricted' => true, 'custom_option' => 'value'];

        $config = new Config(
            'test/key',
            'test_value',
            'Test description',
            TextType::class,
            $formOptions
        );

        self::assertSame($formOptions, $config->formOptions);
        self::assertTrue($config->formOptions['trial_restricted']);
        self::assertSame('value', $config->formOptions['custom_option']);
    }

    public function testConfigWithTrialRestrictedOption(): void
    {
        $config = new Config(
            'system/general/hide_powered_by',
            '0',
            'Hide powered by text',
            TextType::class,
            ['trial_restricted' => true]
        );

        self::assertArrayHasKey('trial_restricted', $config->formOptions);
        self::assertTrue($config->formOptions['trial_restricted']);
    }
}
