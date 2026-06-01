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

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Payum\Core\Model\Identity;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

final class Version30000_11 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_id FK to security_token so that deleting a payment automatically cascades to its tokens';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $tokenTable = $schema->getTable(SecurityToken::TABLE_NAME);

        if (! $tokenTable->hasColumn('payment_id')) {
            $tokenTable->addColumn('payment_id', UlidType::NAME, ['notnull' => false]);
            $tokenTable->addIndex(['payment_id']);
            $tokenTable->addForeignKeyConstraint(
                Payment::TABLE_NAME,
                ['payment_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
            );
        }
    }

    public function postUp(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $rows = $qb
            ->select('t.hash', 't.details')
            ->from(SecurityToken::TABLE_NAME, 't')
            ->where($qb->expr()->isNotNull('t.details'))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $details = @unserialize((string) $row['details']);

            if (! $details instanceof Identity) {
                continue;
            }

            if ($details->getClass() !== Payment::class) {
                continue;
            }

            try {
                $ulid = new Ulid((string) $details->getId());
            } catch (\Throwable) {
                continue;
            }

            $existsQb = $this->connection->createQueryBuilder();
            $exists = $existsQb
                ->select('p.id')
                ->from(Payment::TABLE_NAME, 'p')
                ->where($existsQb->expr()->eq('p.id', ':id'))
                ->setParameter('id', $ulid, UlidType::NAME)
                ->executeQuery()
                ->fetchOne();

            if ($exists === false) {
                continue;
            }

            $updateQb = $this->connection->createQueryBuilder();
            $updateQb
                ->update(SecurityToken::TABLE_NAME)
                ->set('payment_id', ':payment_id')
                ->where($updateQb->expr()->eq('hash', ':hash'))
                ->setParameter('payment_id', $ulid, UlidType::NAME)
                ->setParameter('hash', $row['hash'])
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        $tokenTable = $schema->getTable(SecurityToken::TABLE_NAME);

        if (! $tokenTable->hasColumn('payment_id')) {
            return;
        }

        foreach ($tokenTable->getForeignKeys() as $fk) {
            if (in_array('payment_id', array_map('strtolower', $fk->getLocalColumns()), true)) {
                $tokenTable->removeForeignKey($fk->getName());
            }
        }

        $tokenTable->dropColumn('payment_id');
    }
}
