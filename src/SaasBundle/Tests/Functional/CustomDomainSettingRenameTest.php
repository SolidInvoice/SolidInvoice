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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Doctrine\DBAL\Connection;
use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Zenstruck\Foundry\Test\Factories;

/**
 * Asserts that a value stored under the legacy `system/company/custom_domain`
 * setting key resolves through {@see SystemConfig::get()} at the new
 * `system/domain/custom_domain` key after the rename migration runs.
 */
#[Group('functional')]
final class CustomDomainSettingRenameTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private const string OLD_KEY = 'system/company/custom_domain';

    private const string NEW_KEY = 'system/domain/custom_domain';

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new SaasTestKernel('test', true);
    }

    public function testNewKeyResolvesAfterRename(): void
    {
        $container = self::getContainer();

        /** @var Connection $connection */
        $connection = $container->get('doctrine')->getConnection();

        $companyId = $this->company->getId();

        // Simulate pre-migration state: a row exists at the legacy key with a value.
        // (DefaultData seeded the row with NEW_KEY since the SaasBundle ConfigProvider
        // already uses the renamed key — flip it back so the test exercises the rename.)
        $seeded = $connection->update(
            Setting::TABLE_NAME,
            ['setting_key' => self::OLD_KEY, 'setting_value' => 'invoices.example.com'],
            ['setting_key' => self::NEW_KEY, 'company_id' => $companyId],
            ['company_id' => UlidType::NAME],
        );
        self::assertSame(1, (int) $seeded, 'DefaultData should have seeded exactly one row at the new key.');

        // Apply the rename — same SQL the migration runs.
        $renamed = $connection->update(
            Setting::TABLE_NAME,
            ['setting_key' => self::NEW_KEY],
            ['setting_key' => self::OLD_KEY, 'company_id' => $companyId],
            ['company_id' => UlidType::NAME],
        );
        self::assertSame(1, (int) $renamed);

        // The settings reader is backed by Doctrine, so flush the identity map to make
        // sure we fetch the renamed row from the database rather than the cached entity.
        $container->get('doctrine')->getManager()->clear();

        $config = $container->get(SystemConfig::class);
        self::assertInstanceOf(SystemConfig::class, $config);

        self::assertSame('invoices.example.com', $config->get(self::NEW_KEY));
        self::assertNull($config->get(self::OLD_KEY));
    }

    public function testFreshlySeededRowUsesNewKey(): void
    {
        $config = self::getContainer()->get(SystemConfig::class);
        self::assertInstanceOf(SystemConfig::class, $config);

        self::assertNull($config->get(self::OLD_KEY));

        $config->set(self::NEW_KEY, 'invoices.solo.example');

        self::assertSame('invoices.solo.example', $config->get(self::NEW_KEY));
    }
}
