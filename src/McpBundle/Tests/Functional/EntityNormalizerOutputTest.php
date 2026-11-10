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

namespace SolidInvoice\McpBundle\Tests\Functional;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mcp\Capability\Discovery\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\PaymentTerms;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\McpBundle\Mcp\Output\OutputSchemaGenerator;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteLine;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Component\Uid\Ulid;

/**
 * Pins the exact JSON every MCP entity encodes to: keys, key order, base58 IDs, and money as a
 * decimal string in the document's currency. Entities are built in memory (not through factories)
 * so the save listeners cannot recompute the totals and every value stays literal. Each encoding is
 * also validated against the DTO's generated outputSchema.
 */
#[CoversClass(EntityNormalizer::class)]
final class EntityNormalizerOutputTest extends TestCase
{
    private const string CREATED = '2026-01-02T03:04:05+00:00';

    private const array CLIENT_SUMMARY = ['id' => '1CGw4ifAKQJrAiVSsDDRGU', 'name' => 'Acme Ltd', 'currency' => 'USD'];

    private const array LINE = [
        'id' => '1CGw4ifAKQJrAiVSsDDRGY',
        'name' => 'Consulting',
        'description' => 'Hours',
        'price' => '50.00',
        'qty' => '2',
        'total' => '100.00',
        'taxes' => [[
            'id' => '1CGw4ifAKQJrAiVSsDDRGZ',
            'tax_id' => '1CGw4ifAKQJrAiVSsDDRGW',
            'name' => 'VAT',
            'rate' => '20.0000',
            'type' => 'Exclusive',
            'category' => 'Standard',
            'compound' => false,
            'sequence' => 0,
            'amount' => '20.00',
        ]],
    ];

    // No linked tax rate and no note: the nullable relation must still encode, as null.
    private const array INVOICE_TAX = [
        'id' => '1CGw4ifAKQJrAiVSsDDRGa',
        'tax_id' => null,
        'name' => 'Withholding',
        'rate' => '0.5000',
        'category' => 'Standard',
        'direction' => 'Deductive',
        'sequence' => 0,
        'amount' => '5.00',
        'note' => null,
    ];

    public function testInvoice(): void
    {
        $invoice = $this->withId(new Invoice(), 4)
            ->setInvoiceId('INV-0001')
            ->setStatus(InvoiceStatus::Pending)
            ->setClient($this->client())
            ->setBalance(23456)
            ->setDue(CarbonImmutable::parse('2026-02-01T00:00:00+00:00'))
            ->setInvoiceDate(CarbonImmutable::parse('2026-01-01T00:00:00+00:00'));
        $this->fillDocument($invoice);
        $invoice->setPaymentTerms(PaymentTerms::Custom);
        $invoice->addLine($this->line(new InvoiceLine()));
        $invoice->addInvoiceTax($this->invoiceTax());

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRGX',
            'invoice_number' => 'INV-0001',
            'status' => 'pending',
            'client' => self::CLIENT_SUMMARY,
            'currency' => 'USD',
            'total' => '1234.56',
            'base_total' => '1000.00',
            'balance' => '234.56',
            'tax' => '200.00',
            'withholding_amount' => '5.00',
            'amount_payable' => '1229.56',
            'discount' => ['type' => 'percentage', 'value_money' => '0.00', 'value_percentage' => 10.5],
            'invoice_date' => '2026-01-01T00:00:00+00:00',
            'due' => '2026-02-01T00:00:00+00:00',
            'payment_terms' => 'custom',
            'paid_date' => null,
            'created' => self::CREATED,
            'terms' => 'Net 30',
            'notes' => 'Thanks',
            'lines' => [self::LINE],
            'invoice_taxes' => [self::INVOICE_TAX],
        ], $this->encode($invoice));
    }

    public function testRecurringInvoice(): void
    {
        $recurring = $this->withId(new RecurringInvoice(), 8)
            ->setStatus(RecurringInvoiceStatus::Active)
            ->setClient($this->client())
            ->setDateStart(CarbonImmutable::parse('2026-01-01T00:00:00+00:00'))
            ->setRecurringOptions(new RecurringOptions()
                ->setType(ScheduleRecurringType::WEEKLY)
                ->setDays([1, 3])
                ->setEndType(ScheduleEndType::AFTER)
                ->setEndOccurrence(4));
        $this->fillDocument($recurring);
        $recurring->addLine($this->line(new RecurringInvoiceLine()));
        $recurring->addInvoiceTax($this->invoiceTax());

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRGb',
            'status' => 'active',
            'client' => self::CLIENT_SUMMARY,
            'currency' => 'USD',
            'total' => '1234.56',
            'base_total' => '1000.00',
            'tax' => '200.00',
            'withholding_amount' => '5.00',
            'amount_payable' => '1229.56',
            'date_start' => '2026-01-01T00:00:00+00:00',
            'date_end' => null,
            'schedule' => ['type' => 'weekly', 'days' => [1, 3], 'end_type' => 'after', 'end_date' => null, 'end_occurrence' => 4],
            'generated_invoice_count' => 0,
            'payment_terms' => 'custom',
            'terms' => 'Net 30',
            'notes' => 'Thanks',
            'created' => self::CREATED,
            'lines' => [self::LINE],
            'invoice_taxes' => [self::INVOICE_TAX],
        ], $this->encode($recurring));
    }

    public function testQuote(): void
    {
        $quote = $this->withId(new Quote(), 9)
            ->setStatus(QuoteStatus::Draft)
            ->setClient($this->client())
            ->setDue(null);
        $quote->setQuoteId('QUO-0001');
        $this->fillDocument($quote);
        $quote->addLine($this->line(new QuoteLine()));
        $quote->addInvoiceTax($this->invoiceTax());

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRGc',
            'quote_number' => 'QUO-0001',
            'status' => 'draft',
            'client' => self::CLIENT_SUMMARY,
            'currency' => 'USD',
            'total' => '1234.56',
            'base_total' => '1000.00',
            'tax' => '200.00',
            'withholding_amount' => '5.00',
            'amount_payable' => '1229.56',
            'due' => null,
            'created' => self::CREATED,
            'terms' => 'Net 30',
            'notes' => 'Thanks',
            'lines' => [self::LINE],
            'invoice_taxes' => [self::INVOICE_TAX],
        ], $this->encode($quote));
    }

    public function testDocumentMoneyFollowsItsCurrencyScale(): void
    {
        $invoice = $this->withId(new Invoice(), 4)->setInvoiceId('INV-0002');
        $invoice->setCurrencyCode('BHD');
        $invoice->setTotal(1234);

        $encoded = $this->encode($invoice);

        self::assertSame('BHD', $encoded['currency']);
        self::assertSame('1.234', $encoded['total']);
        self::assertSame('0.000', $encoded['balance']);
    }

    public function testClient(): void
    {
        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRGU',
            'name' => 'Acme Ltd',
            'status' => 'active',
            'website' => 'https://acme.test',
            'currency' => 'USD',
            'tax_identifiers' => [['id' => '1CGw4ifAKQJrAiVSsDDRGV', 'label' => 'VAT', 'value' => 'GB123', 'primary' => true]],
            'created' => self::CREATED,
        ], $this->encode($this->client()));
    }

    public function testContact(): void
    {
        $contact = $this->withId(new Contact(), 10)
            ->setFirstName('Jane')
            ->setLastName('Doe')
            ->setEmail('jane@acme.test')
            ->setClient($this->client());

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRH1',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@acme.test',
            'client' => self::CLIENT_SUMMARY,
        ], $this->encode($contact));
    }

    public function testPayment(): void
    {
        $method = $this->withId(new PaymentMethod(), 11)->setName('Cash')->setGatewayName('offline');
        $payment = $this->payment('USD', 123456)->setMethod($method);

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRH3',
            'status' => 'captured',
            'method' => 'Cash',
            'amount' => '1234.56',
            'currency' => 'USD',
            'invoice_id' => '1CGw4ifAKQJrAiVSsDDRGX',
            'client_id' => '1CGw4ifAKQJrAiVSsDDRGU',
            'created' => self::CREATED,
            'completed' => '2026-01-03T00:00:00+00:00',
        ], $this->encode($payment));
    }

    public function testPaymentWithoutMethod(): void
    {
        self::assertNull($this->encode($this->payment('USD', 100))['method']);
    }

    #[DataProvider('paymentScales')]
    public function testPaymentAmountUsesCurrencyScale(string $currency, int $minor, string $expected): void
    {
        $encoded = $this->encode($this->payment($currency, $minor));

        self::assertSame($expected, $encoded['amount']);
        self::assertSame($currency, $encoded['currency']);
    }

    /**
     * @return iterable<string, array{string, int, string}>
     */
    public static function paymentScales(): iterable
    {
        yield 'USD, scale 2' => ['USD', 1250, '12.50'];
        yield 'JPY, scale 0' => ['JPY', 1500, '1500'];
        yield 'BHD, scale 3' => ['BHD', 1234, '1.234'];
    }

    public function testPaymentMethod(): void
    {
        $method = $this->withId(new PaymentMethod(), 11)->setName('Cash')->setGatewayName('offline')->setEnabled(true);

        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRH2',
            'name' => 'Cash',
            'gateway_name' => 'offline',
            'enabled' => true,
        ], $this->encode($method));
    }

    public function testTax(): void
    {
        self::assertSame([
            'id' => '1CGw4ifAKQJrAiVSsDDRGW',
            'name' => 'VAT',
            'rate' => 7.5,
            'type' => 'Exclusive',
            'category' => 'Standard',
            'compound' => false,
        ], $this->encode($this->tax()));
    }

    public function testTaxIdentifier(): void
    {
        self::assertSame(
            ['id' => '1CGw4ifAKQJrAiVSsDDRGV', 'label' => 'VAT', 'value' => 'GB123', 'primary' => true],
            $this->encode($this->taxIdentifier()),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function encode(object $entity): array
    {
        $output = new EntityNormalizer()->normalize($entity);
        $json = json_encode($output, JSON_THROW_ON_ERROR);

        // The DTO's schema must accept what it encodes to, nulls included.
        $schema = new OutputSchemaGenerator()->generate($output::class);
        self::assertSame([], new SchemaValidator()->validateAgainstJsonSchema(json_decode($json, false, 512, JSON_THROW_ON_ERROR), $schema));

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function fillDocument(Invoice | RecurringInvoice | Quote $document): void
    {
        $document->setTotal(123456);
        $document->setBaseTotal(100000);
        $document->setTax(20000);
        $document->setWithholdingAmount(500);
        $document->setPayableAmount(122956);
        $document->setDiscount(new Discount()->setType(Discount::TYPE_PERCENTAGE)->setValuePercentage(10.5));
        $document->setTerms('Net 30');
        $document->setNotes('Thanks');
        $document->setCreated(new DateTimeImmutable(self::CREATED));
        $document->setCurrencyCode('USD');
    }

    private function client(): Client
    {
        $client = $this->withId(new Client(), 1)
            ->setName('Acme Ltd')
            ->setStatus(ClientStatus::Active)
            ->setWebsite('https://acme.test')
            ->setCurrencyCode('USD');
        $client->setCreated(new DateTimeImmutable(self::CREATED));
        $client->setCompany(new Company());
        $client->addTaxIdentifier($this->taxIdentifier());

        return $client;
    }

    private function taxIdentifier(): TaxIdentifier
    {
        return $this->withId(new TaxIdentifier(), 2)->setLabel('VAT')->setValue('GB123')->setPrimary(true);
    }

    private function tax(): Tax
    {
        return $this->withId(new Tax(), 3)
            ->setName('VAT')
            ->setRate(7.5)
            ->setType(Tax::TYPE_EXCLUSIVE)
            ->setCategory(TaxCategory::Standard)
            ->setCompound(false);
    }

    /**
     * @template T of InvoiceLine|QuoteLine
     *
     * @param T $line
     *
     * @return T
     */
    private function line(InvoiceLine | QuoteLine $line): InvoiceLine | QuoteLine
    {
        $this->withId($line, 5)->setName('Consulting')->setDescription('Hours')->setPrice(5000)->setQty(2)->setTotal(10000);
        $line->addTax($this->withId(new LineTax(), 6)
            ->setTax($this->tax())
            ->setNameSnapshot('VAT')
            ->setRateSnapshot('20')
            ->setTypeSnapshot(TaxType::Exclusive)
            ->setCategorySnapshot(TaxCategory::Standard)
            ->setCompound(false)
            ->setSequence(0)
            ->setAmount(2000));

        return $line;
    }

    private function invoiceTax(): InvoiceTax
    {
        return $this->withId(new InvoiceTax(), 7)
            ->setNameSnapshot('Withholding')
            ->setRateSnapshot('0.5')
            ->setCategorySnapshot(TaxCategory::Standard)
            ->setDirection(TaxDirection::Deductive)
            ->setSequence(0)
            ->setAmount(500);
    }

    private function payment(string $currency, int $minor): Payment
    {
        $invoice = $this->withId(new Invoice(), 4);
        $payment = $this->withId(new Payment(), 12)
            ->setStatus(PaymentStatus::Captured)
            ->setInvoice($invoice)
            ->setClient($this->client())
            ->setCompleted(CarbonImmutable::parse('2026-01-03T00:00:00+00:00'));
        $payment->setTotalAmount($minor);
        $payment->setCurrencyCode($currency);
        $payment->setCreated(new DateTimeImmutable(self::CREATED));

        return $payment;
    }

    /**
     * Entities get their ULID from the Doctrine generator on persist; set a fixed one instead.
     *
     * @template T of object
     *
     * @param T $entity
     *
     * @return T
     */
    private function withId(object $entity, int $sequence): object
    {
        $class = $entity::class;

        while (! property_exists($class, 'id')) {
            $class = get_parent_class($class);
            self::assertIsString($class);
        }

        new ReflectionProperty($class, 'id')->setValue($entity, new Ulid(sprintf('01JC%022d', $sequence)));

        return $entity;
    }
}
