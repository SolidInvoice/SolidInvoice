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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The mapping and {@see \DoctrineMigrations\Version30100_2} have to agree on the column
 * definition, or every deployment ends up with a permanent schema diff.
 */
#[Group('functional')]
final class LineQuantityColumnMatchesMappingTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    /**
     * @return iterable<string, array{string}>
     */
    public static function tables(): iterable
    {
        yield 'invoice_lines' => ['invoice_lines'];
        yield 'quote_lines' => ['quote_lines'];
    }

    #[DataProvider('tables')]
    public function testTheInstalledColumnIsAnExactDecimal(string $table): void
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $connection);

        $column = $connection->createSchemaManager()->introspectTable($table)->getColumn('qty');

        self::assertSame(QuantityType::PRECISION, $column->getPrecision());
        self::assertSame(QuantityType::SCALE, $column->getScale());
        self::assertTrue($column->getNotnull());
    }
}
