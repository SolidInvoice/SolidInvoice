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

        $companyIds = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('companies')
            ->fetchFirstColumn();

        foreach ($companyIds as $companyId) {
            foreach (self::METHODS as $gatewayName => $name) {
                $exists = $this->connection->createQueryBuilder()
                    ->select('1')
                    ->from('payment_methods')
                    ->where('company_id = :company_id')
                    ->andWhere('gateway_name = :gateway_name')
                    ->setParameter('company_id', $companyId)
                    ->setParameter('gateway_name', $gatewayName)
                    ->fetchOne();

                if ($exists !== false) {
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
        $usedMethods = $this->connection->createQueryBuilder()
            ->select('method_id')
            ->from('payments')
            ->where('method_id IS NOT NULL');

        foreach (array_keys(self::METHODS) as $gatewayName) {
            $delete = $this->connection->createQueryBuilder();

            // Only the methods that never took a payment; one that did belongs to the company now.
            $delete
                ->delete('payment_methods')
                ->where('gateway_name = :gateway_name')
                ->andWhere($delete->expr()->notIn('id', $usedMethods->getSQL()))
                ->setParameter('gateway_name', $gatewayName);

            $this->addSql($delete->getSQL(), $delete->getParameters(), $delete->getParameterTypes());
        }
    }
}
