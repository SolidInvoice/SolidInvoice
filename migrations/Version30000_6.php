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
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class Version30000_6 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create MCP server OAuth tables (client, auth code, access/refresh tokens, consent grants)';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        $clientTable = $schema->createTable('mcp_oauth_client');
        $clientTable->addColumn('id', UlidType::NAME);
        $clientTable->addColumn('name', Types::STRING, ['length' => 255]);
        $clientTable->addColumn('redirect_uris', Types::JSON);
        $clientTable->addColumn('grant_types', Types::JSON);
        $clientTable->addColumn('scopes', Types::JSON);
        $clientTable->addColumn('secret_hash', Types::STRING, ['length' => 255, 'notnull' => false]);
        $clientTable->addColumn('token_endpoint_auth_method', Types::STRING, ['length' => 32]);
        $clientTable->addColumn('created_by_id', UlidType::NAME, ['notnull' => false]);
        $clientTable->addColumn('created', Types::DATETIME_MUTABLE);
        $clientTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $clientTable->setPrimaryKey(['id']);
        $clientTable->addForeignKeyConstraint('users', ['created_by_id'], ['id'], ['onDelete' => 'SET NULL']);

        $authCodeTable = $schema->createTable('mcp_oauth_auth_code');
        $authCodeTable->addColumn('id', UlidType::NAME);
        $authCodeTable->addColumn('identifier', Types::STRING, ['length' => 128]);
        $authCodeTable->addColumn('client_id', UlidType::NAME);
        $authCodeTable->addColumn('user_id', UlidType::NAME);
        $authCodeTable->addColumn('company_id', UlidType::NAME);
        $authCodeTable->addColumn('scope_values', Types::JSON);
        $authCodeTable->addColumn('redirect_uri', Types::STRING, ['length' => 2048, 'notnull' => false]);
        $authCodeTable->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $authCodeTable->addColumn('revoked', Types::BOOLEAN, ['default' => false]);
        $authCodeTable->addColumn('created', Types::DATETIME_MUTABLE);
        $authCodeTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $authCodeTable->setPrimaryKey(['id']);
        $authCodeTable->addUniqueIndex(['identifier'], 'uniq_mcp_oauth_auth_code_identifier');
        $authCodeTable->addForeignKeyConstraint('mcp_oauth_client', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
        $authCodeTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $authCodeTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        $accessTokenTable = $schema->createTable('mcp_access_token');
        $accessTokenTable->addColumn('id', UlidType::NAME);
        $accessTokenTable->addColumn('jti', Types::STRING, ['length' => 128]);
        $accessTokenTable->addColumn('client_id', UlidType::NAME);
        $accessTokenTable->addColumn('user_id', UlidType::NAME);
        $accessTokenTable->addColumn('company_id', UlidType::NAME);
        $accessTokenTable->addColumn('scope_values', Types::JSON);
        $accessTokenTable->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $accessTokenTable->addColumn('revoked', Types::BOOLEAN, ['default' => false]);
        $accessTokenTable->addColumn('last_used_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $accessTokenTable->addColumn('created', Types::DATETIME_MUTABLE);
        $accessTokenTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $accessTokenTable->setPrimaryKey(['id']);
        $accessTokenTable->addUniqueIndex(['jti'], 'uniq_mcp_access_token_jti');
        $accessTokenTable->addForeignKeyConstraint('mcp_oauth_client', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
        $accessTokenTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $accessTokenTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        $refreshTokenTable = $schema->createTable('mcp_refresh_token');
        $refreshTokenTable->addColumn('id', UlidType::NAME);
        $refreshTokenTable->addColumn('identifier', Types::STRING, ['length' => 128]);
        $refreshTokenTable->addColumn('access_token_id', UlidType::NAME);
        $refreshTokenTable->addColumn('expires_at', Types::DATETIME_IMMUTABLE);
        $refreshTokenTable->addColumn('revoked', Types::BOOLEAN, ['default' => false]);
        $refreshTokenTable->addColumn('created', Types::DATETIME_MUTABLE);
        $refreshTokenTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $refreshTokenTable->setPrimaryKey(['id']);
        $refreshTokenTable->addUniqueIndex(['identifier'], 'uniq_mcp_refresh_token_identifier');
        $refreshTokenTable->addForeignKeyConstraint('mcp_access_token', ['access_token_id'], ['id'], ['onDelete' => 'CASCADE']);

        $consentTable = $schema->createTable('mcp_consent_grant');
        $consentTable->addColumn('id', UlidType::NAME);
        $consentTable->addColumn('client_id', UlidType::NAME);
        $consentTable->addColumn('user_id', UlidType::NAME);
        $consentTable->addColumn('company_id', UlidType::NAME);
        $consentTable->addColumn('scopes', Types::JSON);
        $consentTable->addColumn('remember_consent', Types::BOOLEAN, ['default' => false]);
        $consentTable->addColumn('created', Types::DATETIME_MUTABLE);
        $consentTable->addColumn('updated', Types::DATETIME_MUTABLE, ['notnull' => false]);
        $consentTable->setPrimaryKey(['id']);
        $consentTable->addUniqueIndex(['client_id', 'user_id', 'company_id'], 'uniq_consent_grant');
        $consentTable->addForeignKeyConstraint('mcp_oauth_client', ['client_id'], ['id'], ['onDelete' => 'CASCADE']);
        $consentTable->addForeignKeyConstraint('users', ['user_id'], ['id'], ['onDelete' => 'CASCADE']);
        $consentTable->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('mcp_consent_grant');
        $schema->dropTable('mcp_refresh_token');
        $schema->dropTable('mcp_access_token');
        $schema->dropTable('mcp_oauth_auth_code');
        $schema->dropTable('mcp_oauth_client');
    }
}
