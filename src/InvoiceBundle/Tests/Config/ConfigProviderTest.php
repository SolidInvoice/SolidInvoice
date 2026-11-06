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

namespace SolidInvoice\InvoiceBundle\Tests\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Config\ConfigProvider;
use SolidInvoice\SettingsBundle\DTO\Config;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testCreditNoteIdPrefixDefaultsToCn(): void
    {
        $configs = new ConfigProvider()->provide([]);

        $prefix = $this->findConfigByKey($configs, 'credit_note/id_generation/id_prefix');

        self::assertNotNull($prefix, 'credit_note/id_generation/id_prefix config not registered');
        self::assertSame('CN-', $prefix->value);
    }

    public function testCreditNoteIdGenerationSectionIsRegistered(): void
    {
        $configs = new ConfigProvider()->provide([]);

        self::assertNotNull($this->findConfigByKey($configs, 'credit_note/id_generation/strategy'));
        self::assertNotNull($this->findConfigByKey($configs, 'credit_note/id_generation/id_suffix'));
    }

    /**
     * @param list<Config> $configs
     */
    private function findConfigByKey(array $configs, string $key): ?Config
    {
        foreach ($configs as $config) {
            if ($config->key === $key) {
                return $config;
            }
        }

        return null;
    }
}
