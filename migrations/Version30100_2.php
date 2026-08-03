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

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;

/**
 * Store line quantities as exact decimals instead of floats.
 *
 * `price × qty` was the one place where an exactly-computed monetary value was multiplied
 * by a float, so the line total depended on how the host happened to round. Existing
 * values convert cleanly: every quantity that a float column could hold exactly is
 * representable in `DECIMAL(20, 6)`, and anything beyond six decimals was noise from the
 * float representation rather than a quantity anyone entered.
 *
 * See {@see QuantityType} for why 20 and 6.
 */
final class Version30100_2 extends AbstractMigration
{
    private const array TABLES = ['invoice_lines', 'quote_lines'];

    /**
     * Frozen at the values {@see QuantityType} used when this migration was written, rather
     * than read from its constants: a migration has to keep describing the schema it
     * produced, so that a later migration that changes the scale has the right starting
     * point to diff against.
     */
    private const int PRECISION = 20;

    private const int SCALE = 6;

    public function getDescription(): string
    {
        return 'Store invoice and quote line quantities as DECIMAL(20, 6) instead of a float';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $column = $schema->getTable($tableName)->getColumn('qty');
            $column->setType(Type::getType(QuantityType::NAME));
            $column->setPrecision(self::PRECISION);
            $column->setScale(self::SCALE);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $tableName) {
            $column = $schema->getTable($tableName)->getColumn('qty');
            $column->setType(Type::getType(Types::FLOAT));
            $column->setPrecision(10);
            $column->setScale(0);
        }
    }
}
