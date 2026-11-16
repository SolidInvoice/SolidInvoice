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

namespace SolidInvoice\InvoiceBundle\Tests\Enum;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\SettingsBundle\SystemConfig;

#[CoversClass(PaymentTerms::class)]
#[CoversClass(Invoice::class)]
final class PaymentTermsTest extends TestCase
{
    /**
     * @return iterable<string, array{PaymentTerms, string, ?string}>
     */
    public static function dueDates(): iterable
    {
        yield 'on receipt is the invoice date' => [PaymentTerms::DueOnReceipt, '2026-01-31 15:00', '2026-01-31'];
        yield 'net 30 crosses a short month' => [PaymentTerms::Net30, '2026-01-31', '2026-03-02'];
        yield 'net 7' => [PaymentTerms::Net7, '2026-12-28', '2027-01-04'];
        yield 'end of month in a leap year' => [PaymentTerms::EndOfMonth, '2028-02-10', '2028-02-29'];
        yield 'end of next month does not overflow' => [PaymentTerms::EndOfNextMonth, '2026-01-31', '2026-02-28'];
        yield 'custom has no rule' => [PaymentTerms::Custom, '2026-01-31', null];
    }

    #[DataProvider('dueDates')]
    public function testDueDate(PaymentTerms $terms, string $invoiceDate, ?string $expected): void
    {
        self::assertSame($expected, $terms->dueDate(CarbonImmutable::parse($invoiceDate))?->format('Y-m-d'));
    }

    public function testPresetTermsOwnTheDueDateWhateverTheSetterOrder(): void
    {
        $invoice = new Invoice()
            ->setPaymentTerms(PaymentTerms::Net14)
            ->setDue(CarbonImmutable::parse('2030-01-01'))
            ->setInvoiceDate(CarbonImmutable::parse('2026-03-01'));

        self::assertSame('2026-03-15', $invoice->getDue()?->format('Y-m-d'));

        $invoice->setInvoiceDate(CarbonImmutable::parse('2026-04-01'));

        self::assertSame('2026-04-15', $invoice->getDue()->format('Y-m-d'));
    }

    public function testCustomTermsKeepTheManualDueDate(): void
    {
        $invoice = new Invoice()
            ->setInvoiceDate(CarbonImmutable::parse('2026-03-01'))
            ->setDue(CarbonImmutable::parse('2026-05-20'));

        self::assertSame(PaymentTerms::Custom, $invoice->getPaymentTerms());
        self::assertSame('2026-05-20', $invoice->getDue()?->format('Y-m-d'));

        $invoice->setPaymentTerms(PaymentTerms::Custom);

        self::assertSame('2026-05-20', $invoice->getDue()->format('Y-m-d'));
    }

    public function testClientTermsBeatTheCompanyDefault(): void
    {
        $config = $this->createStub(SystemConfig::class);
        $config->method('get')->willReturn(PaymentTerms::Net60->value);

        self::assertSame(PaymentTerms::Net60, PaymentTerms::forClient(null, $config));
        self::assertSame(PaymentTerms::Net60, PaymentTerms::forClient(new Client(), $config));
        self::assertSame(PaymentTerms::Net7, PaymentTerms::forClient(new Client()->setPaymentTerms(PaymentTerms::Net7), $config));
    }

    public function testFallsBackToNet30WithoutASetting(): void
    {
        $config = $this->createStub(SystemConfig::class);
        $config->method('get')->willReturn(null);

        self::assertSame(PaymentTerms::Net30, PaymentTerms::forClient(null, $config));
    }
}
