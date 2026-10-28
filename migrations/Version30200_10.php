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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use function mb_substr;
use function trim;

final class Version30200_10 extends AbstractMigration
{
    private const string TABLE_NAME = 'contacts';

    /**
     * The custom fields every company was given for these, now columns.
     */
    private const array COLUMNS = ['phone', 'mobile'];

    public function getDescription(): string
    {
        return 'Move contact phone and mobile numbers from custom fields into columns, so every plan can use them';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        foreach (self::COLUMNS as $column) {
            if (! $table->hasColumn($column)) {
                $table->addColumn($column, Types::STRING, ['length' => 50, 'notnull' => false]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $values = $this->connection->createQueryBuilder()
            ->select('v.target_id', 'f.field_key', 'v.' . $this->connection->quoteSingleIdentifier('value') . ' AS number')
            ->from('custom_field_value', 'v')
            ->innerJoin('v', 'custom_field', 'f', 'f.id = v.field_id')
            ->where("f.target = 'CONTACT'")
            ->andWhere('f.field_key IN (:keys)')
            ->setParameter('keys', self::COLUMNS, ArrayParameterType::STRING)
            ->fetchAllAssociative();

        foreach ($values as $row) {
            $number = trim((string) $row['number']);

            if ($number === '') {
                continue;
            }

            // The column is bounded; a longer value was never a phone number.
            $this->connection->createQueryBuilder()
                ->update(self::TABLE_NAME)
                ->set((string) $row['field_key'], ':number')
                ->where('id = :id')
                ->setParameter('number', mb_substr($number, 0, 50))
                ->setParameter('id', $row['target_id'], UlidType::NAME)
                ->executeStatement();
        }

        $fields = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('custom_field')
            ->where("target = 'CONTACT'")
            ->andWhere('field_key IN (:keys)');

        // The values first: SQLite does not enforce the ON DELETE CASCADE.
        $this->connection->createQueryBuilder()
            ->delete('custom_field_value')
            ->where('field_id IN (' . $fields->getSQL() . ')')
            ->setParameter('keys', self::COLUMNS, ArrayParameterType::STRING)
            ->executeStatement();

        $this->connection->createQueryBuilder()
            ->delete('custom_field')
            ->where("target = 'CONTACT'")
            ->andWhere('field_key IN (:keys)')
            ->setParameter('keys', self::COLUMNS, ArrayParameterType::STRING)
            ->executeStatement();
    }

    /**
     * The numbers go with the columns; the custom fields are not recreated.
     */
    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        foreach (self::COLUMNS as $column) {
            if ($table->hasColumn($column)) {
                $table->dropColumn($column);
            }
        }
    }
}
