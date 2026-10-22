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

use const JSON_THROW_ON_ERROR;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use JsonException;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function bin2hex;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use function strtoupper;

final class Version30200_9 extends AbstractMigration
{
    private const string SETTING_KEY = 'system/company/contact_details/address';

    public function getDescription(): string
    {
        return 'Copy each company postal address out of the '
            . 'system/company/contact_details/address setting into an addresses row';
    }

    public function isTransactional(): bool
    {
        // MySQL and MariaDB commit implicitly on DDL. AbstractMySQLPlatform, because
        // MariaDBPlatform is a sibling of MySQLPlatform rather than a subclass.
        return ! $this->platform instanceof AbstractMySQLPlatform && ! $this->platform instanceof OraclePlatform;
    }

    public function up(Schema $schema): void
    {
        // No DDL. The addresses table already has every column a company address
        // needs, and client_id has been nullable since 2.0 — see the schema document
        // for SOL-72. All the work is a data copy, done in postUp().
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        // Companies that already have a client-less address: re-running this
        // migration, or a company address created since install, must not duplicate it.
        $companiesWithAddress = [];

        foreach ($this->connection->fetchAllAssociative(
            'SELECT company_id FROM addresses WHERE client_id IS NULL'
        ) as $row) {
            $companiesWithAddress[bin2hex((string) $row['company_id'])] = true;
        }

        $legacyRows = $this->connection->fetchAllAssociative(
            'SELECT company_id, setting_value FROM app_config WHERE setting_key = ?',
            [self::SETTING_KEY],
        );

        foreach ($legacyRows as $row) {
            $companyId = $row['company_id'];

            if ($companyId === null) {
                continue;
            }

            $companyKey = bin2hex((string) $companyId);

            if (isset($companiesWithAddress[$companyKey])) {
                continue;
            }

            $settingValue = $row['setting_value'];

            if ($settingValue === null || $settingValue === '') {
                continue;
            }

            try {
                $decoded = json_decode((string) $settingValue, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $this->warnIf(true, sprintf('Skipping malformed company address JSON for company %s.', $companyKey));

                continue;
            }

            // A decoded array with every field null is still a row the settings form
            // saved on purpose (an address cleared to blank) — see schema document §2.2.
            // Only a non-array decode (e.g. a bare JSON string) or [] is skipped.
            if (! is_array($decoded) || [] === $decoded) {
                $this->warnIf(true, sprintf('Skipping non-object company address JSON for company %s.', $companyKey));

                continue;
            }

            $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

            try {
                $this->connection->insert('addresses', [
                    'id' => new Ulid(),
                    'company_id' => $companyId,
                    'client_id' => null,
                    'street1' => $decoded['street1'] ?? null,
                    'street2' => $decoded['street2'] ?? null,
                    'city' => $decoded['city'] ?? null,
                    'state' => $decoded['state'] ?? null,
                    'zip' => $decoded['zip'] ?? null,
                    // Legacy settings predate Address::$country's #[Assert\Country], which
                    // requires an uppercase ISO-3166 code; normalize so a lowercase legacy
                    // value does not fail validation the first time the row is edited.
                    'country' => $this->normalizeCountry($decoded['country'] ?? null),
                    'created' => $now,
                    'updated' => $now,
                ], [
                    'id' => UlidType::NAME,
                    'company_id' => UlidType::NAME,
                ]);
            } catch (Exception $e) {
                // A database that reached 3.x by running migrations alone can still have
                // addresses.id as an untyped integer (schema document §4) — guard per row
                // so one bad database does not fail the whole upgrade.
                $this->warnIf(true, sprintf('Failed to insert company address for company %s: %s', $companyKey, $e->getMessage()));

                continue;
            }

            $companiesWithAddress[$companyKey] = true;
        }
    }

    public function down(Schema $schema): void
    {
        if (! $schema->hasTable('addresses')) {
            return;
        }

        // Reversible without data loss: the setting row this migration copied from is
        // kept for a release (SOL-72 design D3), and CompanyAddressManager::store()
        // mirrors every later edit back onto both the setting and this row, so the two
        // never drift. This assumes a client-less address row only ever represents a
        // company's postal address; a future client-less address type would need its
        // own column or table, not a reuse of this one, or this delete would wipe it too.
        $this->connection->executeStatement('DELETE FROM addresses WHERE client_id IS NULL');
    }

    private function normalizeCountry(mixed $country): ?string
    {
        if (! is_string($country) || '' === $country) {
            return null;
        }

        return strtoupper($country);
    }
}
