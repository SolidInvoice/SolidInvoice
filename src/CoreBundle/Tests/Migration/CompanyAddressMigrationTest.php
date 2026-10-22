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

namespace SolidInvoice\CoreBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use DoctrineMigrations\Version30200_9;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * `postUp()` copies each company's legacy `system/company/contact_details/address`
 * setting into a client-less `addresses` row. Built like
 * {@see LinePositionBackfillMigrationTest}: SQLite in-memory, hand-built fixture
 * tables, `postUp()` called directly.
 *
 * @see Version30200_9
 */
final class CompanyAddressMigrationTest extends TestCase
{
    private const string SETTING_KEY = 'system/company/contact_details/address';

    private Connection $connection;

    protected function setUp(): void
    {
        // The type is registered by the DBAL config in a booted kernel; this test runs
        // without one.
        if (! Type::hasType(UlidType::NAME)) {
            Type::addType(UlidType::NAME, UlidType::class);
        }

        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            'CREATE TABLE addresses (id BLOB NOT NULL PRIMARY KEY, company_id BLOB DEFAULT NULL, client_id BLOB DEFAULT NULL, street1 VARCHAR(255) DEFAULT NULL, street2 VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, zip VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, created DATETIME DEFAULT NULL, updated DATETIME DEFAULT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE app_config (id BLOB NOT NULL PRIMARY KEY, company_id BLOB DEFAULT NULL, setting_key VARCHAR(125) NOT NULL, setting_value TEXT DEFAULT NULL)'
        );
    }

    /**
     * @throws Exception
     */
    public function testPostUpInsertsARowFromAValidPayload(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '{"street1":"123 Main St","street2":"Suite 4","city":"Cape Town","state":"WC","zip":"8000","country":"ZA"}');

        $this->migration()->postUp(new Schema());

        $rows = $this->addressesFor($companyId);
        self::assertCount(1, $rows);
        self::assertSame('123 Main St', $rows[0]['street1']);
        self::assertSame('Suite 4', $rows[0]['street2']);
        self::assertSame('Cape Town', $rows[0]['city']);
        self::assertSame('WC', $rows[0]['state']);
        self::assertSame('8000', $rows[0]['zip']);
        self::assertSame('ZA', $rows[0]['country']);
        self::assertNull($rows[0]['client_id']);
    }

    /**
     * @throws Exception
     */
    public function testPostUpInsertsARowForAnAllNullPayload(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '{"street1":null,"street2":null,"city":null,"state":null,"zip":null,"country":null}');

        $this->migration()->postUp(new Schema());

        $rows = $this->addressesFor($companyId);
        self::assertCount(1, $rows);
        self::assertNull($rows[0]['street1']);
        self::assertNull($rows[0]['country']);
    }

    /**
     * @throws Exception
     */
    public function testPostUpSkipsANullSettingValue(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, null);

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->addressesFor($companyId));
    }

    /**
     * @throws Exception
     */
    public function testPostUpSkipsAnEmptySettingValue(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '');

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->addressesFor($companyId));
    }

    /**
     * @throws Exception
     */
    public function testPostUpSkipsARowWithNoCompanyId(): void
    {
        $this->insertSetting(null, '{"street1":"Orphan"}');

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->connection->fetchAllAssociative('SELECT * FROM addresses'));
    }

    /**
     * @throws Exception
     */
    public function testPostUpSkipsMalformedJson(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '{"street1": "123 Main St"');

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->addressesFor($companyId));
    }

    /**
     * The same raw, unquoted shape DashboardBundle/Tests/Checklist/Items/ChecklistItemsTest
     * writes for this setting — a legacy value that predates the JSON encoding.
     *
     * @throws Exception
     */
    public function testPostUpSkipsABareJsonString(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '123 Main St');

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->addressesFor($companyId));
    }

    /**
     * @throws Exception
     */
    public function testPostUpSkipsAnEmptyJsonArray(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '[]');

        $this->migration()->postUp(new Schema());

        self::assertCount(0, $this->addressesFor($companyId));
    }

    /**
     * Legacy settings predate Address::$country's #[Assert\Country], which requires an
     * uppercase ISO-3166 code. A lowercase legacy value must not fail validation the
     * first time someone edits the backfilled row.
     *
     * @throws Exception
     */
    public function testPostUpUppercasesALowercaseLegacyCountryCode(): void
    {
        $companyId = new Ulid();
        $this->insertSetting($companyId, '{"country":"za"}');

        $this->migration()->postUp(new Schema());

        self::assertSame('ZA', $this->addressesFor($companyId)[0]['country']);
    }

    /**
     * Re-running this migration, or a company address created since install, must not
     * duplicate an existing client-less row.
     *
     * @throws Exception
     */
    public function testPostUpDoesNotDuplicateAnExistingClientlessAddress(): void
    {
        $companyId = new Ulid();
        $this->insertAddress($companyId, null, ['street1' => 'Existing']);
        $this->insertSetting($companyId, '{"street1":"From setting"}');

        $this->migration()->postUp(new Schema());

        $rows = $this->addressesFor($companyId);
        self::assertCount(1, $rows);
        self::assertSame('Existing', $rows[0]['street1']);
    }

    /**
     * A company with only client-owned addresses has no client-less row yet, so the
     * legacy setting still needs to be copied over for it.
     *
     * @throws Exception
     */
    public function testPostUpStillInsertsARowForACompanyWithOnlyClientOwnedAddresses(): void
    {
        $companyId = new Ulid();
        $this->insertAddress($companyId, new Ulid(), ['street1' => 'Client address']);
        $this->insertSetting($companyId, '{"street1":"Company address"}');

        $this->migration()->postUp(new Schema());

        $rows = $this->addressesFor($companyId);
        self::assertCount(1, $rows);
        self::assertSame('Company address', $rows[0]['street1']);
    }

    /**
     * @throws Exception
     */
    public function testPostUpKeepsEachCompanysRowIsolated(): void
    {
        $companyA = new Ulid();
        $companyB = new Ulid();
        $this->insertSetting($companyA, '{"street1":"Company A"}');
        $this->insertSetting($companyB, '{"street1":"Company B"}');

        $this->migration()->postUp(new Schema());

        self::assertSame('Company A', $this->addressesFor($companyA)[0]['street1']);
        self::assertSame('Company B', $this->addressesFor($companyB)[0]['street1']);
    }

    /**
     * Reversible without data loss: the setting row this migration copies from is kept
     * for a release, so deleting the copies loses nothing — but a client-owned address
     * must survive the rollback.
     *
     * @throws Exception
     */
    public function testDownDeletesOnlyClientlessAddresses(): void
    {
        $companyId = new Ulid();
        $clientId = new Ulid();
        $this->insertAddress($companyId, null, ['street1' => 'Company address']);
        $this->insertAddress($companyId, $clientId, ['street1' => 'Client address']);

        // down() guards on $schema->hasTable('addresses') before touching data; the
        // schema here only needs to declare the table, not match the real columns.
        $schema = new Schema();
        $schema->createTable('addresses');

        $this->migration()->down($schema);

        $remaining = $this->connection->fetchAllAssociative('SELECT street1 FROM addresses');
        self::assertCount(1, $remaining);
        self::assertSame('Client address', $remaining[0]['street1']);
    }

    private function migration(): Version30200_9
    {
        return new Version30200_9($this->connection, new NullLogger());
    }

    /**
     * @throws Exception
     */
    private function insertSetting(?Ulid $companyId, ?string $value): void
    {
        $this->connection->insert('app_config', [
            'id' => new Ulid(),
            'company_id' => $companyId,
            'setting_key' => self::SETTING_KEY,
            'setting_value' => $value,
        ], [
            'id' => UlidType::NAME,
            'company_id' => UlidType::NAME,
        ]);
    }

    /**
     * @param array<string, string|null> $fields
     *
     * @throws Exception
     */
    private function insertAddress(Ulid $companyId, ?Ulid $clientId, array $fields = []): void
    {
        $this->connection->insert('addresses', [
            'id' => new Ulid(),
            'company_id' => $companyId,
            'client_id' => $clientId,
            'street1' => $fields['street1'] ?? null,
            'street2' => $fields['street2'] ?? null,
            'city' => $fields['city'] ?? null,
            'state' => $fields['state'] ?? null,
            'zip' => $fields['zip'] ?? null,
            'country' => $fields['country'] ?? null,
        ], [
            'id' => UlidType::NAME,
            'company_id' => UlidType::NAME,
            'client_id' => UlidType::NAME,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws Exception
     */
    private function addressesFor(Ulid $companyId): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM addresses WHERE company_id = ? AND client_id IS NULL',
            [$companyId],
            [UlidType::NAME],
        );
    }
}
