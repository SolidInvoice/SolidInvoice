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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Intl\Countries;
use function is_array;
use function is_string;
use function json_decode;
use function strtoupper;

final class Version30200_5 extends AbstractMigration
{
    private const string TABLE_NAME = 'companies';

    private const string ADDRESS_SETTING = 'system/company/contact_details/address';

    public function getDescription(): string
    {
        return 'Give companies an owner, a country and created/updated timestamps, backfilled from their users and address';
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

        if (! $table->hasColumn('owner_id')) {
            $table->addColumn('owner_id', UlidType::NAME, ['notnull' => false]);
            $table->addIndex(['owner_id']);
            $table->addForeignKeyConstraint('users', ['owner_id'], ['id'], ['onDelete' => 'SET NULL']);
        }

        if (! $table->hasColumn('country')) {
            $table->addColumn('country', Types::STRING, ['length' => 2, 'notnull' => false]);
        }

        // Nullable until postUp() fills them; Version30200_6 then makes them NOT NULL.
        foreach (['created', 'updated'] as $column) {
            if (! $table->hasColumn($column)) {
                $table->addColumn($column, Types::DATETIME_IMMUTABLE, ['notnull' => false]);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // The owner is the company's earliest-registered user, and the company is taken to
        // have been created when they registered. A company with no users keeps no owner.
        $firstUser = fn (string $column): string => '(' . $this->connection->createQueryBuilder()
            ->select('u.' . $column)
            ->from('user_company', 'uc')
            ->innerJoin('uc', 'users', 'u', 'u.id = uc.user_id')
            ->where('uc.company_id = ' . self::TABLE_NAME . '.id')
            ->orderBy('u.created', 'ASC')
            ->addOrderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getSQL() . ')';

        $this->connection->createQueryBuilder()
            ->update(self::TABLE_NAME)
            ->set('owner_id', $firstUser('id'))
            ->where('owner_id IS NULL')
            ->executeStatement();

        $this->connection->createQueryBuilder()
            ->update(self::TABLE_NAME)
            ->set('created', 'COALESCE(' . $firstUser('created') . ', ' . $this->platform->getCurrentTimestampSQL() . ')')
            ->where('created IS NULL')
            ->executeStatement();

        $this->connection->createQueryBuilder()
            ->update(self::TABLE_NAME)
            ->set('updated', 'created')
            ->where('updated IS NULL')
            ->executeStatement();

        $this->backfillCountries();
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(self::TABLE_NAME);

        foreach ($table->getForeignKeys() as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['owner_id']) {
                $table->removeForeignKey($foreignKey->getName());
            }
        }

        foreach ($table->getIndexes() as $index) {
            if ($index->getColumns() === ['owner_id']) {
                $table->dropIndex($index->getName());
            }
        }

        foreach (['owner_id', 'country', 'created', 'updated'] as $column) {
            if ($table->hasColumn($column)) {
                $table->dropColumn($column);
            }
        }
    }

    /**
     * A company that filled in its address already told us its country.
     *
     * @throws Exception
     */
    private function backfillCountries(): void
    {
        $addresses = $this->connection->createQueryBuilder()
            ->select('company_id', 'setting_value')
            ->from('app_config')
            ->where('setting_key = :key')
            ->andWhere('setting_value IS NOT NULL')
            ->setParameter('key', self::ADDRESS_SETTING)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($addresses as $row) {
            $address = json_decode((string) $row['setting_value'], true);
            $country = is_array($address) && is_string($address['country'] ?? null) ? strtoupper($address['country']) : null;

            if ($country === null || ! Countries::exists($country)) {
                continue;
            }

            $this->connection->createQueryBuilder()
                ->update(self::TABLE_NAME)
                ->set('country', ':country')
                ->where('id = :id')
                ->andWhere('country IS NULL')
                ->setParameter('country', $country)
                ->setParameter('id', $row['company_id'])
                ->executeStatement();
        }
    }
}
