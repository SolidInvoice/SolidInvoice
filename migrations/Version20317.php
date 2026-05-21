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
use Doctrine\Migrations\Exception\IrreversibleMigration;
use RuntimeException;

/**
 * Re-hashes every existing API token with HMAC-SHA256 keyed by the
 * application secret. Prior to this migration, tokens were stored in
 * plaintext; afterwards the database only ever contains the hash.
 *
 * Existing tokens continue to work because authentication now hashes the
 * inbound plaintext before lookup using the same algorithm and key.
 */
final class Version20317 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $secret = $_SERVER['SOLIDINVOICE_APP_SECRET'] ?? $_ENV['SOLIDINVOICE_APP_SECRET'] ?? null;

        if (! is_string($secret) || '' === $secret) {
            throw new RuntimeException(
                'SOLIDINVOICE_APP_SECRET must be set to run this migration; it is the key used to hash API tokens.'
            );
        }

        $tableName = $this->platform->quoteIdentifier('api_tokens');
        $idColumn = $this->platform->quoteIdentifier('id');
        $tokenColumn = $this->platform->quoteIdentifier('token');

        $rows = $this->connection->createQueryBuilder()
            ->select($idColumn, $tokenColumn)
            ->from($tableName)
            ->executeQuery()
            ->iterateAssociative();

        foreach ($rows as $row) {
            $this->connection->update(
                'api_tokens',
                ['token' => hash_hmac('sha256', (string) $row['token'], $secret)],
                ['id' => $row['id']],
            );
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'API token hashes cannot be reversed back to plaintext. To roll back, revoke all tokens and have users create new ones.'
        );
    }
}
