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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('functional')]
final class CustomFieldsMigrationTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testNewTablesExist(): void
    {
        /** @var Connection $conn */
        $conn = self::getContainer()->get('doctrine.dbal.default_connection');
        $sm = $conn->createSchemaManager();

        self::assertTrue($sm->tablesExist(['custom_field']), 'Table custom_field should exist after migration');
        self::assertTrue($sm->tablesExist(['custom_field_value']), 'Table custom_field_value should exist after migration');
    }

    public function testLegacyTablesAreGone(): void
    {
        /** @var Connection $conn */
        $conn = self::getContainer()->get('doctrine.dbal.default_connection');
        $sm = $conn->createSchemaManager();

        self::assertFalse($sm->tablesExist(['contact_types']), 'Legacy table contact_types should not exist after migration');
        self::assertFalse($sm->tablesExist(['contact_details']), 'Legacy table contact_details should not exist after migration');
    }
}
