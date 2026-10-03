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

namespace SolidInvoice\CoreBundle\Tests\Migration\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\CoreBundle\Doctrine\Migrations\DryRunAwareMigration;

/**
 * Reproduces the SOL-155 hazard on a throwaway table: a `preDown()` hook that mutates a
 * row through `$this->connection` directly, the same way {@see \DoctrineMigrations\Version30100_7::preDown()}
 * and {@see \DoctrineMigrations\Version30100_6::preDown()} do, guarded the same way they are.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Migration\MigrationDryRunGuardTest
 */
final class MutatingPreDownMigrationFixture extends AbstractMigration
{
    use DryRunAwareMigration;

    public const string TABLE_NAME = 'dry_run_guard_fixture';

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', Types::INTEGER);
        $table->addColumn('value', Types::STRING, ['length' => 32]);
        $table->addColumn('marker', Types::STRING, ['length' => 32]);
        $table->setPrimaryKey(['id']);
    }

    public function preDown(Schema $schema): void
    {
        if ($this->isDryRun()) {
            return;
        }

        $this->connection->update(self::TABLE_NAME, ['value' => 'mutated-by-predown'], ['id' => 1]);
    }

    public function down(Schema $schema): void
    {
        $schema->getTable(self::TABLE_NAME)->dropColumn('marker');
    }
}
