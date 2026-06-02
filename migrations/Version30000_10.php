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
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version30000_10 extends AbstractMigration
{
    /**
     * @var list<array<string, mixed>>
     */
    private array $legacyTypes = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $legacyValues = [];

    public function getDescription(): string
    {
        return 'Add custom_field and custom_field_value tables; migrate legacy contact_type/additional_contact_detail data; drop legacy tables.';
    }

    public function isTransactional(): bool
    {
        return ! $this->platform instanceof MySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // Capture legacy rows before dropping tables.
        if ($schema->hasTable('contact_types')) {
            $this->legacyTypes = $this->connection->fetchAllAssociative(
                'SELECT id, company_id, name, type, field_options, required FROM contact_types'
            );
        }

        if ($schema->hasTable('contact_details')) {
            $this->legacyValues = $this->connection->fetchAllAssociative(
                'SELECT id, company_id, contact_type_id AS type_id, contact_id, value, created, updated FROM contact_details'
            );
        }

        // 1. Create custom_field
        $cf = $schema->createTable('custom_field');
        $cf->addColumn('id', 'ulid', ['notnull' => true]);
        $cf->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cf->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('label', 'string', ['length' => 125, 'notnull' => true]);
        $cf->addColumn('field_key', 'string', ['length' => 64, 'notnull' => true]);
        $cf->addColumn('type', 'string', ['length' => 32, 'notnull' => true]);
        $cf->addColumn('options', 'json', ['notnull' => false]);
        $cf->addColumn('required', 'boolean', ['notnull' => true, 'default' => false]);
        $cf->addColumn('position', 'integer', ['notnull' => true, 'default' => 0]);
        $cf->addColumn('default_value', 'text', ['notnull' => false]);
        $cf->addColumn('visibility', 'string', ['length' => 32, 'notnull' => false]);
        $cf->addColumn('created', 'datetime', ['notnull' => true]);
        $cf->addColumn('updated', 'datetime', ['notnull' => true]);
        $cf->setPrimaryKey(['id']);
        $cf->addUniqueIndex(['company_id', 'target', 'field_key'], 'uq_cf_company_target_key');
        $cf->addIndex(['company_id', 'target', 'position'], 'idx_cf_company_target_pos');
        $cf->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 2. Create custom_field_value
        $cfv = $schema->createTable('custom_field_value');
        $cfv->addColumn('id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('company_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('field_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('target', 'string', ['length' => 32, 'notnull' => true]);
        $cfv->addColumn('target_id', 'ulid', ['notnull' => true]);
        $cfv->addColumn('value', 'text', ['notnull' => false]);
        $cfv->addColumn('created', 'datetime', ['notnull' => true]);
        $cfv->addColumn('updated', 'datetime', ['notnull' => true]);
        $cfv->setPrimaryKey(['id']);
        $cfv->addUniqueIndex(['field_id', 'target_id'], 'uq_cfv_field_record');
        $cfv->addIndex(['company_id', 'target', 'target_id'], 'idx_cfv_company_target_record');
        $cfv->addIndex(['field_id'], 'idx_cfv_field');
        $cfv->addForeignKeyConstraint('companies', ['company_id'], ['id'], ['onDelete' => 'CASCADE']);
        $cfv->addForeignKeyConstraint('custom_field', ['field_id'], ['id'], ['onDelete' => 'CASCADE']);

        // 3. Drop legacy tables (data already captured above; inserts happen in postUp).
        if ($schema->hasTable('contact_details')) {
            $schema->dropTable('contact_details');
        }

        if ($schema->hasTable('contact_types')) {
            $schema->dropTable('contact_types');
        }
    }

    public function postUp(Schema $schema): void
    {
        // 3a. Copy contact_types → custom_field
        $seenKeysByCompany = [];
        $positionByCompany = [];

        foreach ($this->legacyTypes as $r) {
            $companyKey = bin2hex((string) $r['company_id']);
            $baseKey = $this->slugify((string) $r['name']);
            $key = $baseKey;
            $i = 2;

            while (isset($seenKeysByCompany[$companyKey][$key])) {
                $key = $baseKey . '_' . $i++;
            }

            $seenKeysByCompany[$companyKey][$key] = true;

            $position = $positionByCompany[$companyKey] ?? 0;
            $positionByCompany[$companyKey] = $position + 1;

            $oldType = strtolower((string) ($r['type'] ?? 'text'));
            $newType = match ($oldType) {
                'email' => 'email',
                default => 'text',
            };

            $oldOptions = $r['field_options'];
            $optionsJson = null;

            if (is_string($oldOptions) && $oldOptions !== '') {
                $decoded = @unserialize($oldOptions, ['allowed_classes' => false]);

                if (is_array($decoded) && $decoded !== []) {
                    $shaped = [];

                    foreach ($decoded as $k => $v) {
                        $value = is_int($k) ? (is_array($v) ? implode(', ', $v) : (string) $v) : (string) $k;
                        $label = is_array($v) ? implode(', ', $v) : (string) $v;
                        $shaped[] = ['value' => $value, 'label' => $label];
                    }

                    $optionsJson = json_encode($shaped, JSON_THROW_ON_ERROR);
                }
            }

            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $this->connection->insert('custom_field', [
                'id' => $r['id'],
                'company_id' => $r['company_id'],
                'target' => 'CONTACT',
                'label' => (string) $r['name'],
                'field_key' => $key,
                'type' => $newType,
                'options' => $optionsJson,
                'required' => (int) (bool) $r['required'],
                'position' => $position,
                'created' => $now,
                'updated' => $now,
            ]);
        }

        // 3b. Copy contact_details → custom_field_value
        // The legacy schema allowed multiple values per (contact_type_id, contact_id) pair,
        // but the new unique constraint uq_cfv_field_record only permits one. Keep the last seen value.
        $seenFieldContact = [];
        foreach ($this->legacyValues as $r) {
            $pairKey = bin2hex((string) $r['type_id']) . '_' . bin2hex((string) $r['contact_id']);
            $seenFieldContact[$pairKey] = $r;
        }

        foreach ($seenFieldContact as $r) {
            $this->connection->insert('custom_field_value', [
                'id' => $r['id'],
                'company_id' => $r['company_id'],
                'field_id' => $r['type_id'],
                'target' => 'CONTACT',
                'target_id' => $r['contact_id'],
                'value' => $r['value'],
                'created' => $r['created'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated' => $r['updated'] ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        throw new IrreversibleMigration(
            'This migration restructures contact types into a unified custom-field schema. ' .
            'Reversing would lose data. Restore from backup.'
        );
    }

    private function slugify(string $input): string
    {
        $s = strtolower(trim($input));
        $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
        $s = trim($s, '_');

        if ($s === '' || ! preg_match('/^[a-z]/', $s)) {
            $s = 'field_' . $s;
        }

        return substr($s, 0, 64);
    }
}
