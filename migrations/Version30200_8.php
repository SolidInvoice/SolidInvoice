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

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final class Version30200_8 extends AbstractMigration
{
    /**
     * Gateway name => name. New companies get the name translated; existing ones get English, and
     * can rename it.
     */
    private const array METHODS = [
        'cheque' => 'Cheque',
        'card_terminal' => 'Card terminal',
    ];

    public function getDescription(): string
    {
        return 'Add the Cheque and Card terminal offline payment methods to every company that lacks them';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        $now = new DateTimeImmutable();

        foreach ($this->connection->fetchFirstColumn('SELECT id FROM companies') as $companyId) {
            foreach (self::METHODS as $gatewayName => $name) {
                $exists = $this->connection->fetchOne(
                    'SELECT 1 FROM payment_methods WHERE company_id = ? AND gateway_name = ?',
                    [$companyId, $gatewayName]
                );

                if ($exists !== false) {
                    continue;
                }

                $this->addSql(
                    'INSERT INTO payment_methods (id, company_id, name, gateway_name, factory, config, internal, enabled, created, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [new Ulid(), $companyId, $name, $gatewayName, 'offline', [], true, true, $now, $now],
                    // Unqualified, the ULIDs bind as text and PostgreSQL rejects the raw bytes as invalid UTF-8.
                    [UlidType::NAME, UlidType::NAME, Types::STRING, Types::STRING, Types::STRING, Types::JSON, Types::BOOLEAN, Types::BOOLEAN, Types::DATETIME_IMMUTABLE, Types::DATETIME_IMMUTABLE]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (array_keys(self::METHODS) as $gatewayName) {
            // Only the methods that never took a payment; one that did belongs to the company now.
            $this->addSql(
                'DELETE FROM payment_methods WHERE gateway_name = ? AND id NOT IN (SELECT method_id FROM payments WHERE method_id IS NOT NULL)',
                [$gatewayName]
            );
        }
    }
}
