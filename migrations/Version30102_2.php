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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use function array_unique;
use function count;
use function in_array;
use function sprintf;
use function strlen;
use function trim;

/**
 * Freezes `currency_code` on pre-upgrade invoices and quotes that were already issued before
 * {@see Version30100_7} added the column, to the value they are already displaying — a
 * pre-upgrade issued document has `currency_code IS NULL` and so falls back to the client's
 * current currency in {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency::getCurrency()},
 * which is exactly the value this migration writes. The write is therefore a visual no-op on
 * the day of the upgrade: what changes is only that the document stops restating itself when
 * the client's currency changes later.
 *
 * `recurring_invoices` is deliberately out of scope: it is never issued, nothing freezes it on
 * the forward path, and a recurring invoice template is meant to keep following its client.
 *
 * A document whose currency cannot be resolved without guessing (conflicting payments, no
 * client or company currency, cancelled with no evidence it was ever issued) is left NULL and
 * counted in the migration output.
 *
 * Reads go through the query builder and writes through Connection::update(), and the
 * resolution itself is done in PHP, so no platform-specific SQL is involved. ID values are
 * passed back exactly as they were read, so they keep whatever representation the platform
 * stores ULIDs in.
 *
 * On the in-app upgrade path ({@see \SolidInvoice\InstallBundle\Installer\Database\Migration})
 * every migration is stamped applied from a schema diff rather than executed, so this backfill
 * does not run there and cannot be run afterwards once the version is stamped. It is effective
 * only via `bin/console doctrine:migrations:migrate`, until SOL-173 lands.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Migration\DocumentCurrencyBackfillMigrationTest
 */
final class Version30102_2 extends AbstractMigration
{
    private const array INVOICE_ISSUED_STATUSES = ['pending', 'overdue', 'paid'];

    private const array QUOTE_ISSUED_STATUSES = ['pending', 'accepted', 'declined'];

    // Issued only if there is positive evidence of it; see the per-document checks below.
    private const array CANCELLED_STATUSES = ['cancelled', 'archived'];

    public function getDescription(): string
    {
        return 'Backfill the frozen currency_code onto pre-upgrade issued invoices and quotes';
    }

    public function up(Schema $schema): void
    {
        // Data only; see postUp().
    }

    /**
     * @throws Exception
     */
    public function postUp(Schema $schema): void
    {
        $companyCurrencies = $this->companyCurrencies();

        $this->report('invoices', $this->backfillInvoices($companyCurrencies));
        $this->report('quotes', $this->backfillQuotes($companyCurrencies));
    }

    public function down(Schema $schema): void
    {
        // Nothing to undo: the frozen value is the one each document was already displaying,
        // and Version30100_7's down() drops the column outright.
    }

    /**
     * @param array<string, string> $companyCurrencies
     *
     * @return array<string, int> outcome => count
     *
     * @throws Exception
     */
    private function backfillInvoices(array $companyCurrencies): array
    {
        $invoices = $this->connection->createQueryBuilder()
            ->select('i.id', 'i.company_id', 'i.status', 'i.paid_date', 'c.currency AS client_currency')
            ->from('invoices', 'i')
            ->leftJoin('i', 'clients', 'c', 'c.id = i.client_id AND c.company_id = i.company_id')
            ->where('i.currency_code IS NULL')
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('statuses', [...self::INVOICE_ISSUED_STATUSES, ...self::CANCELLED_STATUSES], ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchAllAssociative();

        // Payments only count when they belong to the invoice's own company.
        $payments = $this->connection->createQueryBuilder()
            ->select('p.invoice_id', 'p.currency_code')
            ->from('payments', 'p')
            ->innerJoin('p', 'invoices', 'i', 'i.id = p.invoice_id AND i.company_id = p.company_id')
            ->where('i.currency_code IS NULL')
            ->executeQuery()
            ->fetchAllAssociative();

        $paymentCurrencies = [];
        foreach ($payments as $payment) {
            $paymentCurrencies[$payment['invoice_id']][] = $payment['currency_code'];
        }

        $outcomes = [];

        // ponytail: one UPDATE per document, fine for a one-off backfill; batch it if a tenant's upgrade is too slow.
        foreach ($invoices as $invoice) {
            $currencies = $paymentCurrencies[$invoice['id']] ?? [];

            $issued = in_array($invoice['status'], self::INVOICE_ISSUED_STATUSES, true)
                || null !== $invoice['paid_date']
                || [] !== $currencies;

            if (! $issued) {
                // Cancelled or archived with no positive evidence it was ever issued: reported, not guessed.
                $outcome = 'indeterminate_cancelled';
            } elseif ([] === $currencies) {
                // No payments: fall back to the client's currency, company setting second.
                $outcome = $this->freezeFromFallback('invoices', $invoice, $companyCurrencies);
            } elseif (1 === count(array_unique($currencies)) && ! $this->hasBlank($currencies)) {
                // Every payment agrees on a non-blank currency: that currency wins.
                $outcome = $this->freezeCurrency('invoices', $invoice['id'], $currencies[0]);
            } else {
                // The payments disagree, or one has no currency: a data problem to surface, never a fallback to the client.
                $outcome = 'conflicting_payments';
            }

            $outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;
        }

        return $outcomes;
    }

    /**
     * @param array<string, string> $companyCurrencies
     *
     * @return array<string, int> outcome => count
     *
     * @throws Exception
     */
    private function backfillQuotes(array $companyCurrencies): array
    {
        $quotes = $this->connection->createQueryBuilder()
            ->select('q.id', 'q.company_id', 'q.status', 'c.currency AS client_currency')
            ->from('quotes', 'q')
            ->leftJoin('q', 'clients', 'c', 'c.id = q.client_id AND c.company_id = q.company_id')
            ->where('q.currency_code IS NULL')
            ->andWhere('q.status IN (:statuses)')
            ->setParameter('statuses', [...self::QUOTE_ISSUED_STATUSES, ...self::CANCELLED_STATUSES], ArrayParameterType::STRING)
            ->executeQuery()
            ->fetchAllAssociative();

        // A quote with a derived invoice in its own company must have reached accepted.
        $convertedQuoteIds = $this->connection->createQueryBuilder()
            ->select('DISTINCT i.quote_id')
            ->from('invoices', 'i')
            ->innerJoin('i', 'quotes', 'q', 'q.id = i.quote_id AND q.company_id = i.company_id')
            ->where('q.currency_code IS NULL')
            ->executeQuery()
            ->fetchFirstColumn();

        $converted = [];
        foreach ($convertedQuoteIds as $quoteId) {
            $converted[$quoteId] = true;
        }

        $outcomes = [];

        foreach ($quotes as $quote) {
            // Quotes have no payment tier: straight to the client, company setting second.
            $outcome = in_array($quote['status'], self::QUOTE_ISSUED_STATUSES, true) || isset($converted[$quote['id']])
                ? $this->freezeFromFallback('quotes', $quote, $companyCurrencies)
                : 'indeterminate_cancelled';

            $outcomes[$outcome] = ($outcomes[$outcome] ?? 0) + 1;
        }

        return $outcomes;
    }

    /**
     * Each company's own currency setting, keyed by company ID; only a 3-letter value counts.
     *
     * @return array<string, string>
     *
     * @throws Exception
     */
    private function companyCurrencies(): array
    {
        $settings = $this->connection->createQueryBuilder()
            ->select('company_id', 'setting_value')
            ->from('app_config')
            ->where('setting_key = :key')
            ->setParameter('key', 'system/company/currency')
            ->executeQuery()
            ->fetchAllAssociative();

        $currencies = [];
        foreach ($settings as $setting) {
            $currency = trim((string) $setting['setting_value']);

            if (3 === strlen($currency)) {
                $currencies[$setting['company_id']] = $currency;
            }
        }

        return $currencies;
    }

    /**
     * Client currency first, company setting second; neither resolving — which covers a
     * document with no client — is reported as unresolvable.
     *
     * @param array<string, mixed>  $document
     * @param array<string, string> $companyCurrencies
     *
     * @throws Exception
     */
    private function freezeFromFallback(string $table, array $document, array $companyCurrencies): string
    {
        $currency = trim((string) $document['client_currency']);

        if ('' === $currency) {
            $currency = $companyCurrencies[$document['company_id']] ?? null;
        }

        return null === $currency ? 'unresolvable_currency' : $this->freezeCurrency($table, $document['id'], $currency);
    }

    /**
     * @throws Exception
     */
    private function freezeCurrency(string $table, mixed $id, string $currency): string
    {
        $this->connection->update($table, ['currency_code' => $currency], ['id' => $id, 'currency_code' => null]);

        return 'frozen';
    }

    /**
     * @param list<?string> $currencies
     */
    private function hasBlank(array $currencies): bool
    {
        foreach ($currencies as $currency) {
            if (null === $currency || '' === trim($currency)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, int> $outcomes
     */
    private function report(string $table, array $outcomes): void
    {
        foreach ($outcomes as $outcome => $count) {
            $this->write(sprintf('%s: %d %s', $table, $count, $outcome));
        }
    }
}
