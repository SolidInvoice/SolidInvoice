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
use DoctrineMigrations\Version30102_2;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * `postUp()` is where a pre-upgrade issued invoice or quote gets the `currency_code` the freeze
 * listener would have written had it existed when the document was issued — reconstructed from
 * its payments, then its client, then the company default, or left NULL when that would be a guess.
 *
 * @see Version30102_2
 */
final class DocumentCurrencyBackfillMigrationTest extends TestCase
{
    private Connection $connection;

    private TestHandler $log;

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

        $this->log = new TestHandler();
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

        $this->migrate();

        self::assertSame('EUR', $this->currencyCodeOf('invoices', 'invoice-1'));
    }

    /**
     * @throws Exception
     */
    public function testClientSourcedCurrencyWhenNoPayments(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migrate();

        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-1'));
        self::assertTrue($this->log->hasNotice('invoices: 1 frozen'));
    }

    /**
     * Also covers AC6: two companies' invoices each resolve to their own company's setting, never
     * the other's, which only holds if the company currency lookup is keyed by company_id.
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

        $this->migrate();

        self::assertSame('ZAR', $this->currencyCodeOf('invoices', 'invoice-a'));
        self::assertSame('JPY', $this->currencyCodeOf('invoices', 'invoice-b'));
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

        $this->migrate();

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-new'));
        self::assertNull($this->currencyCodeOf('invoices', 'invoice-draft'));
        self::assertNull($this->currencyCodeOf('quotes', 'quote-new'));
        self::assertNull($this->currencyCodeOf('quotes', 'quote-draft'));
        self::assertSame([], $this->log->getRecords());
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

        $this->migrate();

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-mixed'));
        self::assertNull($this->currencyCodeOf('invoices', 'invoice-blank'));
        self::assertTrue($this->log->hasNotice('invoices: 2 conflicting_payments'));
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

        $this->migrate();

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

        $this->migrate();

        self::assertNull($this->currencyCodeOf('invoices', 'invoice-cancelled'));
        self::assertNull($this->currencyCodeOf('invoices', 'invoice-archived'));
        self::assertTrue($this->log->hasNotice('invoices: 2 indeterminate_cancelled'));
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

        $this->migrate();

        self::assertSame('GBP', $this->currencyCodeOf('quotes', 'quote-with-invoice'));
        self::assertNull($this->currencyCodeOf('quotes', 'quote-without-invoice'));
        self::assertTrue($this->log->hasNotice('quotes: 1 indeterminate_cancelled'));
    }

    /**
     * @throws Exception
     */
    public function testQuoteWithNoResolvableCurrencyIsReportedUnresolvable(): void
    {
        $this->insertQuote('quote-no-client', 'company-a', 'pending', clientId: null);

        $this->migrate();

        self::assertNull($this->currencyCodeOf('quotes', 'quote-no-client'));
        self::assertTrue($this->log->hasNotice('quotes: 1 unresolvable_currency'));
    }

    /**
     * @throws Exception
     */
    public function testRowAlreadyHoldingACurrencyIsUntouched(): void
    {
        $this->insertInvoice('invoice-frozen', 'company-a', 'pending', clientId: 'client-1', currencyCode: 'JPY');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migrate();

        self::assertSame('JPY', $this->currencyCodeOf('invoices', 'invoice-frozen'));
        self::assertSame([], $this->log->getRecords());
    }

    /**
     * @throws Exception
     */
    public function testPostUpRunTwiceWritesNothingTheSecondTime(): void
    {
        $this->insertInvoice('invoice-1', 'company-a', 'pending', clientId: 'client-1');
        $this->insertClient('client-1', 'company-a', 'GBP');

        $this->migrate();
        $this->log->clear();
        $this->migrate();

        self::assertSame('GBP', $this->currencyCodeOf('invoices', 'invoice-1'));
        self::assertSame([], $this->log->getRecords());
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

        $this->migrate();

        self::assertNull($this->currencyCodeOf('recurring_invoices', 'recurring-1'));
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
     * @throws Exception
     */
    private function migrate(): void
    {
        new Version30102_2($this->connection, new Logger('migration', [$this->log]))->postUp(new Schema());
    }
}
