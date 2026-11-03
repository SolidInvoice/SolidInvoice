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
use Doctrine\DBAL\ArrayParameterType;
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

        $companyIds = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('companies')
            ->fetchFirstColumn();

        $existing = [];

        $rows = $this->connection->createQueryBuilder()
            ->select('company_id', 'gateway_name')
            ->from('payment_methods')
            ->where('gateway_name IN (:gateway_names)')
            ->setParameter('gateway_names', array_keys(self::METHODS), ArrayParameterType::STRING)
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $existing[$row['company_id'] . '|' . $row['gateway_name']] = true;
        }

        foreach ($companyIds as $companyId) {
            foreach (self::METHODS as $gatewayName => $name) {
                if (isset($existing[$companyId . '|' . $gatewayName])) {
                    continue;
                }

                $insert = $this->connection->createQueryBuilder()
                    ->insert('payment_methods')
                    ->values([
                        'id' => ':id',
                        'company_id' => ':company_id',
                        'name' => ':name',
                        'gateway_name' => ':gateway_name',
                        'factory' => ':factory',
                        'config' => ':config',
                        'internal' => ':internal',
                        'enabled' => ':enabled',
                        'created' => ':created',
                        'updated' => ':updated',
                    ])
                    // Unqualified, the ULIDs bind as text and PostgreSQL rejects the raw bytes as invalid UTF-8.
                    ->setParameter('id', new Ulid(), UlidType::NAME)
                    ->setParameter('company_id', $companyId, UlidType::NAME)
                    ->setParameter('name', $name)
                    ->setParameter('gateway_name', $gatewayName)
                    ->setParameter('factory', 'offline')
                    ->setParameter('config', [], Types::JSON)
                    ->setParameter('internal', true, Types::BOOLEAN)
                    ->setParameter('enabled', true, Types::BOOLEAN)
                    ->setParameter('created', $now, Types::DATETIME_IMMUTABLE)
                    ->setParameter('updated', $now, Types::DATETIME_IMMUTABLE);

                $this->addSql($insert->getSQL(), $insert->getParameters(), $insert->getParameterTypes());
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Nothing to undo: a company may have had these methods before up() ran, or edited them since,
        // and up() skips any that exist, so running it again after a rollback is safe.
    }
}
