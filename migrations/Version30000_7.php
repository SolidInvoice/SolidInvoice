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
use Doctrine\Migrations\AbstractMigration;

final class Version30000_7 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert time-tracking invoice line qty from fractional hours to seconds';
    }

    public function up(Schema $schema): void
    {
        // No schema changes — qty column stays FLOAT
    }

    public function postUp(Schema $schema): void
    {
        // Convert fractional hours → seconds for existing time-tracking lines
        $this->connection->createQueryBuilder()
            ->update('invoice_lines')
            ->set('qty', 'ROUND(qty * 3600)')
            ->where('line_item_type = :type')
            ->setParameter('type', 'time_tracking')
            ->executeStatement();
    }

    public function down(Schema $schema): void
    {
    }

    public function postDown(Schema $schema): void
    {
        $this->connection->createQueryBuilder()
            ->update('invoice_lines')
            ->set('qty', 'qty / 3600.0')
            ->where('line_item_type = :type')
            ->setParameter('type', 'time_tracking')
            ->executeStatement();
    }
}
