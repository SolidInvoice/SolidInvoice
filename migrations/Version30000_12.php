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

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use SolidInvoice\UserBundle\Entity\UserInvitation;

final class Version30000_12 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add expires_at and reminder_sent_at to user_invitations so invitations have a limited validity period and a tracked expiry reminder';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable(UserInvitation::TABLE_NAME);

        if (! $table->hasColumn('expires_at')) {
            $table->addColumn('expires_at', Types::DATETIMETZ_IMMUTABLE, ['notnull' => false]);
        }

        if (! $table->hasColumn('reminder_sent_at')) {
            $table->addColumn('reminder_sent_at', Types::DATETIMETZ_IMMUTABLE, ['notnull' => false]);
        }
    }

    public function postUp(Schema $schema): void
    {
        $qb = $this->connection->createQueryBuilder();
        $rows = $qb
            ->select('i.id', 'i.created')
            ->from(UserInvitation::TABLE_NAME, 'i')
            ->where($qb->expr()->isNull('i.expires_at'))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $expiresAt = CarbonImmutable::parse((string) $row['created'])
                ->addDays(UserInvitation::VALIDITY_DAYS);

            $updateQb = $this->connection->createQueryBuilder();
            $updateQb
                ->update(UserInvitation::TABLE_NAME)
                ->set('expires_at', ':expires_at')
                ->where($updateQb->expr()->eq('id', ':id'))
                ->setParameter('expires_at', $expiresAt, Types::DATETIMETZ_IMMUTABLE)
                ->setParameter('id', $row['id'])
                ->executeStatement();
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable(UserInvitation::TABLE_NAME);

        if ($table->hasColumn('reminder_sent_at')) {
            $table->dropColumn('reminder_sent_at');
        }

        if ($table->hasColumn('expires_at')) {
            $table->dropColumn('expires_at');
        }
    }
}
