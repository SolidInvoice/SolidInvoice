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
use DoctrineMigrations\Version30100_21;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * `postUp()` is where a pre-upgrade issued invoice or quote gets the `currency_code` the freeze
 * listener would have written had it existed when the document was issued — reconstructed from
 * its payments, then its client, then the company default, and captured for reversal either way.
 *
 * The `Schema` object `postUp()` receives in this harness is inert, so `document_currency_backfill`
 * is created by hand in {@see setUp()} mirroring {@see Version30100_21::up()} — which is the gap
 * {@see DocumentCurrencyBackfillColumnsMatchMappingTest} closes.
 *
 * @see Version30100_21
 */
final class DocumentCurrencyBackfillMigrationTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $this->connection->executeStatement(
            'CREATE TABLE invoices (id VARCHAR(26) NOT NULL PRIMARY KEY, company_id VARCHAR(26) NOT NULL, client_id VARCHAR(26) DEFAULT NULL, quote_id VARCHAR(26) DEFAULT NULL, status VARCHAR(25) NOT NULL, currency_code VARCHAR(3) DEFAULT NULL, paid_date DATETIME DEFAULT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE quotes (id VARCHAR(26) NOT NULL PRIMARY KEY, company_id VARCHAR(26) NOT NULL, client_id VARCHAR(26) DEFAULT NULL, status VARCHAR(25) NOT NULL, currency_code VARCHAR(3) DEFAULT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE payments (id VARCHAR(26) NOT NULL PRIMARY KEY, invoice_id VARCHAR(26) DEFAULT NULL, company_id VARCHAR(26) NOT NULL, currency_code VARCHAR(255) DEFAULT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE clients (id VARCHAR(26) NOT NULL PRIMARY KEY, company_id VARCHAR(26) NOT NULL, currency VARCHAR(3) DEFAULT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE app_config (setting_key VARCHAR(125) NOT NULL, setting_value TEXT DEFAULT NULL, company_id VARCHAR(26) NOT NULL)'
        );

        $this->connection->executeStatement(
            'CREATE TABLE recurring_invoices (id VARCHAR(26) NOT NULL PRIMARY KEY, company_id VARCHAR(26) NOT NULL, status VARCHAR(25) NOT NULL, currency_code VARCHAR(3) DEFAULT NULL)'
        );

        // Mirrors Version30100_21::up(): this harness never calls up(), so postUp() needs the
        // capture table to already exist.
        $this->connection->executeStatement(
            'CREATE TABLE document_currency_backfill (document_type VARCHAR(20) NOT NULL, document_id VARCHAR(26) NOT NULL, company_id VARCHAR(26) NOT NULL, currency_code VARCHAR(3) DEFAULT NULL, source VARCHAR(20) DEFAULT NULL, outcome VARCHAR(32) NOT NULL, captured_at DATETIME NOT NULL, PRIMARY KEY (document_type, document_id))'
        );
    }

    /**
     * @throws Exception
     */
    public function testPaymentSourcedCurrencyWinsWhenAllPaymentsAgree(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'USD');
        $this->insertPayment('payment-1', 'invoice-1', 'company-a', 'EUR');
        $this->insertPayment('payment-2', 'invoice-1', 'company-a', 'EUR');

        $this->migration()->postUp(new Schema());

        self::assertSame('EUR', $this->currencyCodeOf('invoices', 'invoice-1'));
        $capture = $this->captureRow('invoice', 'invoice-1');
        self::assertSame('EUR', $capture['currency_code']);
        self::assertSame('payment', $capture['source']);
        self::assertSame('frozen', $capture['outcome']);
    }

    /**
     * @throws Exception
     */
    public function testClientSourcedCurrencyWhenNoPayments(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-1'));
        $capture = $this->captureRow('invoice', 'invoice-1');
        self::assertSame('client', $capture['source']);
        self::assertSame('frozen', $capture['outcome']);
    }

    /**
     * Also covers AC6: two companies' invoices each resolve to their own company's setting, never
     * the other's, which only holds if the `app_config` join carries the company_id equality.
     *
     * @throws Exception
     */
    public function testCompanySourcedCurrencyWhenClientCurrencyIsNullAndCompaniesDoNotLeak(): void
    {
        $this->insertInvoice('invoice-a', 'company-a', 'pending', clientId: 'client-a');
        $this->insertClient('client-a', 'company-a', null);
        $this->insertAppConfig('company-a', 'ZAR');

        $this->insertInvoice('invoice-b', 'company-b', 'pending', clientId: 'client-b');
        $this->insertClient('client-b', 'company-b', null);
        $this->insertAppConfig('company-b', 'JPY');

        $this->migration()->postUp(new Schema());

        self::assertSame('ZAR', $this->currencyCodeOf('invoices', 'invoice-a'));
        self::assertSame('company', $this->captureRow('invoice', 'invoice-a')['source']);

        self::assertSame('JPY', $this->currencyCodeOf('invoices', 'invoice-b'));
        self::assertSame('company', $this->captureRow('invoice', 'invoice-b')['source']);
    }

    /**
     * @throws Exception
     */
    public function testDraftAndNewDocumentsAreSkippedSilently(): void
    {
        $this->insertInvoice('invoice-new', 'company-a', 'new', clientId: 'client-1');
        $this->insertInvoice('invoice-draft', 'company-a', 'draft', clientId: 'client-1');
        $this->insertQuote('quote-new', 'company-a', 'new', clientId: 'client-1');
        $this->insertQuote('quote-draft', 'company-a', 'draft', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-new'));
        self::assertNull($this->currencyCodeOf('invoices', 'invoice-draft'));
        self::assertNull($this->currencyCodeOf('quotes', 'quote-new'));
        self::assertNull($this->currencyCodeOf('quotes', 'quote-draft'));
        self::assertSame([], $this->allCaptureRows());
    }

    /**
     * @throws Exception
     */
    public function testConflictingOrBlankPaymentCurrenciesAreReportedNotGuessed(): void
    {
        $this->insertInvoice('invoice-mixed', 'company-a', 'pending', clientId: 'client-1');
        $this->insertPayment('payment-1', 'invoice-mixed', 'company-a', 'EUR');
        $this->insertPayment('payment-2', 'invoice-mixed', 'company-a', 'USD');

        $this->insertInvoice('invoice-blank', 'company-a', 'pending', clientId: 'client-1');
        $this->insertPayment('payment-3', 'invoice-blank', 'company-a', '');

        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-mixed'));
        self::assertSame('conflicting_payments', $this->captureRow('invoice', 'invoice-mixed')['outcome']);
        self::assertNull($this->captureRow('invoice', 'invoice-mixed')['currency_code']);

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-blank'));
        self::assertSame('conflicting_payments', $this->captureRow('invoice', 'invoice-blank')['outcome']);
    }

    /**
     * @throws Exception
     */
    public function testCancelledInvoiceWithEvidenceIsBackfilled(): void
    {
        $this->insertInvoice('invoice-paid-date', 'company-a', 'cancelled', clientId: 'client-1', paidDate: '2025-01-01');
        $this->insertInvoice('invoice-with-payment', 'company-a', 'archived', clientId: 'client-1');
        $this->insertPayment('payment-1', 'invoice-with-payment', 'company-a', 'EUR');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-paid-date'));
        self::assertSame('EUR', $this->currencyCodeOf('invoices', 'invoice-with-payment'));
    }

    /**
     * @throws Exception
     */
    public function testCancelledInvoiceWithoutEvidenceIsReportedIndeterminate(): void
    {
        $this->insertInvoice('invoice-cancelled', 'company-a', 'cancelled', clientId: 'client-1');
        $this->insertInvoice('invoice-archived', 'company-a', 'archived', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-cancelled'));
        self::assertSame('indeterminate_cancelled', $this->captureRow('invoice', 'invoice-cancelled')['outcome']);

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-archived'));
        self::assertSame('indeterminate_cancelled', $this->captureRow('invoice', 'invoice-archived')['outcome']);
    }

    /**
     * @throws Exception
     */
    public function testArchivedQuoteWithDerivedInvoiceIsBackfilledOtherwiseIndeterminate(): void
    {
        $this->insertQuote('quote-with-invoice', 'company-a', 'archived', clientId: 'client-1');
        $this->insertInvoice('invoice-from-quote', 'company-a', 'pending', clientId: 'client-1', quoteId: 'quote-with-invoice');

        $this->insertQuote('quote-without-invoice', 'company-a', 'archived', clientId: 'client-1');

        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertSame('GBP', $this->currencyCodeOf('quotes', 'quote-with-invoice'));

        self::assertNull($this->currencyCodeOf('quotes', 'quote-without-invoice'));
        self::assertSame('indeterminate_cancelled', $this->captureRow('quote', 'quote-without-invoice')['outcome']);
    }

    /**
     * @throws Exception
     */
    public function testQuoteWithNoResolvableCurrencyIsReportedUnresolvable(): void
    {
        $this->insertQuote('quote-no-client', 'company-a', 'pending', clientId: null);

        $this->migration()->postUp(new Schema());

        self::assertNull($this->currencyCodeOf('quotes', 'quote-no-client'));
        self::assertSame('unresolvable_currency', $this->captureRow('quote', 'quote-no-client')['outcome']);
    }

    /**
     * @throws Exception
     */
    public function testRowAlreadyHoldingACurrencyIsUntouchedAndNotCaptured(): void
    {
        $this->insertInvoice('invoice-frozen', 'company-a', 'pending', clientId: 'client-1', currencyCode: 'JPY');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migration()->postUp(new Schema());

        self::assertSame('JPY', $this->currencyCodeOf('invoices', 'invoice-frozen'));
        self::assertNull($this->captureRow('invoice', 'invoice-frozen'));
    }

    /**
     * @throws Exception
     */
    public function testPostUpRunTwiceWritesNothingTheSecondTime(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $migration = $this->migration();
        $migration->postUp(new Schema());

        $rowsAfterFirstRun = $this->allCaptureRows();

        $migration->postUp(new Schema());

        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-1'));
        self::assertSame($rowsAfterFirstRun, $this->allCaptureRows());
    }

    /**
     * @throws Exception
     */
    public function testPreDownRestoresExactlyWhatWasBackfilled(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $migration = $this->migration();
        $migration->postUp(new Schema());
        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-1'));

        $migration->preDown(new Schema());

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-1'));
    }

    /**
     * @throws Exception
     */
    public function testPreDownLeavesChangedOrUncapturedRowsAlone(): void
    {
        $this->insertInvoice('invoice-changed', 'company-a', 'pending', clientId: 'client-1');
        $this->insertInvoice('invoice-never-captured', 'company-a', 'pending', clientId: 'client-1', currencyCode: 'CHF');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $migration = $this->migration();
        $migration->postUp(new Schema());

        // A hand edit after the backfill: the capture no longer matches the stored value.
        $this->connection->update('invoices', ['currency_code' => 'AUD'], ['id' => 'invoice-changed']);

        $migration->preDown(new Schema());

        self::assertSame('AUD', $this->currencyCodeOf('invoices', 'invoice-changed'));
        self::assertSame('CHF', $this->currencyCodeOf('invoices', 'invoice-never-captured'));
    }

    /**
     * @throws Exception
     */
    public function testRecurringInvoicesAreNeverTouched(): void
    {
        $this->connection->insert('recurring_invoices', [
            'id' => 'recurring-1',
            'company_id' => 'company-a',
            'status' => 'pending',
            'currency_code' => null,
        ]);

        $this->migration()->postUp(new Schema());

        self::assertNull($this->currencyCodeOf('recurring_invoices', 'recurring-1'));
        self::assertNull($this->captureRow('recurring_invoice', 'recurring-1'));
    }

    /**
     * @throws Exception
     */
    private function insertInvoice(
        string $id,
        string $companyId,
        string $status,
        ?string $clientId = null,
        ?string $quoteId = null,
        ?string $currencyCode = null,
        ?string $paidDate = null,
    ): void {
        $this->connection->insert('invoices', [
            'id' => $id,
            'company_id' => $companyId,
            'client_id' => $clientId,
            'quote_id' => $quoteId,
            'status' => $status,
            'currency_code' => $currencyCode,
            'paid_date' => $paidDate,
        ]);
    }

    /**
     * @throws Exception
     */
    private function insertQuote(string $id, string $companyId, string $status, ?string $clientId = null, ?string $currencyCode = null): void
    {
        $this->connection->insert('quotes', [
            'id' => $id,
            'company_id' => $companyId,
            'client_id' => $clientId,
            'status' => $status,
            'currency_code' => $currencyCode,
        ]);
    }

    /**
     * @throws Exception
     */
    private function insertPayment(string $id, string $invoiceId, string $companyId, ?string $currencyCode): void
    {
        $this->connection->insert('payments', [
            'id' => $id,
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
            'currency_code' => $currencyCode,
        ]);
    }

    /**
     * @throws Exception
     */
    private function insertClient(string $id, string $companyId, ?string $currency): void
    {
        $this->connection->insert('clients', [
            'id' => $id,
            'company_id' => $companyId,
            'currency' => $currency,
        ]);
    }

    /**
     * @throws Exception
     */
    private function insertAppConfig(string $companyId, string $value): void
    {
        $this->connection->insert('app_config', [
            'setting_key' => 'system/company/currency',
            'setting_value' => $value,
            'company_id' => $companyId,
        ]);
    }

    /**
     * @throws Exception
     */
    private function currencyCodeOf(string $table, string $id): ?string
    {
        $value = $this->connection->fetchOne(
            "SELECT currency_code FROM {$table} WHERE id = ?",
            [$id],
        );

        return false === $value ? null : $value;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws Exception
     */
    private function captureRow(string $documentType, string $documentId): ?array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM document_currency_backfill WHERE document_type = ? AND document_id = ?',
            [$documentType, $documentId],
        );

        return false === $row ? null : $row;
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws Exception
     */
    private function allCaptureRows(): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM document_currency_backfill ORDER BY document_type, document_id',
        );
    }

    private function migration(): Version30100_21
    {
        return new Version30100_21($this->connection, new NullLogger());
    }
}
