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

namespace SolidInvoice\PaymentBundle\Tests\Functional\Api;

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('functional')]
final class RecordPaymentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testRecordPayment(): void
    {
        $invoice = $this->createInvoice();

        $result = $this->postPayment($invoice, [
            'amount' => '150.00',
            'currency' => 'USD',
            'method' => 'bank_transfer',
            'paidAt' => CarbonImmutable::now()->subDays(3)->toDateString(),
            'reference' => 'EFT 8812',
            'notes' => 'Paid on site',
        ])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('captured', $result['status']);
        self::assertEqualsCanonicalizing(['gatewayName', 'name'], array_values(array_diff(array_keys($result['method']), ['@id', '@type'])));
        self::assertSame('bank_transfer', $result['method']['gatewayName']);
        self::assertSame('USD', $result['currencyCode']);
        self::assertSame('EFT 8812', $result['reference']);
        self::assertSame('Paid on site', $result['notes']);
        self::assertSame($this->getIriFromResource($invoice), $result['invoice']);
        self::assertStringStartsWith(CarbonImmutable::now()->subDays(3)->toDateString(), $result['paidAt']);
        self::assertEquals(0, $result['creditAdded']);
        self::assertArrayHasKey('amount', $result);

        foreach (['details', 'creditCard', 'bankAccount', 'totalAmount', 'clientEmail', 'number', 'description'] as $internal) {
            self::assertArrayNotHasKey($internal, $result);
        }

        self::assertSame(15000, $this->onlyPayment($invoice)->getTotalAmount());
    }

    public function testAPartialPaymentKeepsTheInvoiceOpenWithAReducedBalance(): void
    {
        $invoice = $this->createInvoice();

        $this->postPayment($invoice, ['amount' => '150.00']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $invoice = $this->reload($invoice);
        self::assertSame(InvoiceStatus::Pending, $invoice->getStatus());
        self::assertTrue(BigInteger::of(85000)->isEqualTo($invoice->getBalance()));
        self::assertNull($invoice->getPaidDate());

        $this->postPayment($invoice, ['amount' => '850.00']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $invoice = $this->reload($invoice);
        self::assertSame(InvoiceStatus::Paid, $invoice->getStatus());
        self::assertTrue($invoice->getBalance()->isZero());
        self::assertNotNull($invoice->getPaidDate());
    }

    public function testAnOverpaymentMarksTheInvoicePaidAndCreditsTheExcess(): void
    {
        $invoice = $this->createInvoice();

        $result = $this->postPayment($invoice, ['amount' => 1200.0])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertEquals(200, $result['creditAdded']);

        $invoice = $this->reload($invoice);
        self::assertSame(InvoiceStatus::Paid, $invoice->getStatus());
        $client = $invoice->getClient();
        self::assertInstanceOf(Client::class, $client);
        self::assertTrue(BigInteger::of(20000)->isEqualTo($client->getCredit()->getValue()));
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function creditOverdrawnProvider(): iterable
    {
        yield 'more than the client has' => [5000, '60.00', 'Not have enough credit available'];
        yield 'more than the invoice balance' => [500000, '1000.01', 'Amount captured exceeds outstanding balance'];
    }

    #[DataProvider('creditOverdrawnProvider')]
    public function testACreditPaymentCannotOverdrawTheCreditOrTheBalance(int $credit, string $amount, string $message): void
    {
        $invoice = $this->createInvoice();
        $this->giveCredit($invoice, $credit);

        $result = $this->postPayment($invoice, ['amount' => $amount, 'method' => 'credit'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('amount', $result['violations'][0]['propertyPath']);
        self::assertSame($message, $result['violations'][0]['message']);
    }

    public function testACreditPaymentSpendsTheClientsCredit(): void
    {
        $invoice = $this->createInvoice();
        $this->giveCredit($invoice, 30000);

        $this->postPayment($invoice, ['amount' => '100.00', 'method' => 'credit']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $client = $this->reload($invoice)->getClient();
        self::assertInstanceOf(Client::class, $client);
        self::assertTrue(BigInteger::of(20000)->isEqualTo($client->getCredit()->getValue()));
    }

    /**
     * @return iterable<string, array{string, string|float, int}>
     */
    public static function currencyScaleProvider(): iterable
    {
        yield 'JPY has no decimals' => ['JPY', '1250', 1250];
        yield 'BHD has three' => ['BHD', '12.500', 12500];
        yield 'USD from a JSON float' => ['USD', 12.5, 1250];
    }

    #[DataProvider('currencyScaleProvider')]
    public function testTheAmountIsScaledByTheInvoiceCurrency(string $currency, string | float $amount, int $minor): void
    {
        $invoice = $this->createInvoice($currency);

        $this->postPayment($invoice, ['amount' => $amount, 'currency' => $currency]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame($minor, $this->onlyPayment($invoice)->getTotalAmount());
    }

    public function testMorePrecisionThanTheCurrencyAllowsIsRejected(): void
    {
        $invoice = $this->createInvoice('BHD');

        $result = $this->postPayment($invoice, ['amount' => '12.5005'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('amount', $result['violations'][0]['propertyPath']);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidAmountProvider(): iterable
    {
        yield 'zero' => ['0.00'];
        yield 'negative' => ['-5'];
        yield 'not a number' => ['ten'];
    }

    #[DataProvider('invalidAmountProvider')]
    public function testAnInvalidAmountIsRejected(mixed $amount): void
    {
        $result = $this->postPayment($this->createInvoice(), ['amount' => $amount])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('amount', $result['violations'][0]['propertyPath']);
    }

    public function testALegacyIntegerAmountIsReadAsMinorUnitsAndFlaggedDeprecated(): void
    {
        $invoice = $this->createInvoice();

        $response = $this->postPayment($invoice, ['amount' => 1000]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame(1000, $this->onlyPayment($invoice)->getTotalAmount());

        $headers = $response->getHeaders(false);
        self::assertArrayHasKey('deprecation', $headers);
        self::assertStringContainsString('rel="deprecation"', implode(',', $headers['link'] ?? []));
    }

    public function testADecimalAmountIsNotFlaggedDeprecated(): void
    {
        $response = $this->postPayment($this->createInvoice(), ['amount' => '10.00']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertStringNotContainsString('rel="deprecation"', implode(',', $response->getHeaders(false)['link'] ?? []));
    }

    public function testTheMethodDefaultsToCash(): void
    {
        $invoice = $this->createInvoice();

        $this->postPayment($invoice, ['amount' => '10.00']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('cash', $this->onlyPayment($invoice)->getMethod()?->getGatewayName());
    }

    public function testACustomOfflineMethodCanBeSelected(): void
    {
        PaymentMethodFactory::createOne(['gatewayName' => 'mpesa', 'factoryName' => 'offline', 'enabled' => true, 'internal' => false]);
        $invoice = $this->createInvoice();

        $this->postPayment($invoice, ['amount' => '10.00', 'method' => 'mpesa']);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('mpesa', $this->onlyPayment($invoice)->getMethod()?->getGatewayName());
    }

    /**
     * @return iterable<string, array{array<string, mixed>|null}>
     */
    public static function unusableMethodProvider(): iterable
    {
        yield 'unknown' => [null];
        yield 'disabled' => [['factoryName' => 'offline', 'enabled' => false, 'internal' => true]];
        yield 'online' => [['factoryName' => 'stripe_checkout', 'enabled' => true, 'internal' => false]];
    }

    /**
     * @param array<string, mixed>|null $method
     */
    #[DataProvider('unusableMethodProvider')]
    public function testAnUnusableMethodIsRejected(?array $method): void
    {
        if ($method !== null) {
            PaymentMethodFactory::createOne(['gatewayName' => 'chosen', ...$method]);
        }

        $result = $this->postPayment($this->createInvoice(), ['amount' => '10.00', 'method' => 'chosen'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('method', $result['violations'][0]['propertyPath']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPaidAtProvider(): iterable
    {
        yield 'in the future' => [CarbonImmutable::now()->addDays(3)->toDateString()];
        yield 'over a year before the invoice' => [CarbonImmutable::now()->subYears(2)->toDateString()];
    }

    #[DataProvider('invalidPaidAtProvider')]
    public function testAnInvalidPaymentDateIsRejected(string $paidAt): void
    {
        $result = $this->postPayment($this->createInvoice(), ['amount' => '10.00', 'paidAt' => $paidAt])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('paidAt', $result['violations'][0]['propertyPath']);
    }

    /**
     * The invoice was issued while the client was in EUR and keeps that frozen currency even
     * though the client has since moved to USD. The payment must be validated against what the
     * invoice itself states, not the client's current currency.
     */
    public function testRecordPaymentAcceptsTheInvoicesFrozenCurrencyEvenAfterTheClientMoves(): void
    {
        $invoice = $this->createInvoice('USD', ['currencyCode' => 'EUR']);

        $result = $this->postPayment($invoice, ['amount' => '10.00', 'currency' => 'EUR'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('EUR', $result['currencyCode']);
    }

    public function testRecordPaymentRejectsACurrencyOtherThanTheInvoices(): void
    {
        $invoice = $this->createInvoice('USD', ['currencyCode' => 'EUR']);

        $result = $this->postPayment($invoice, ['amount' => '10.00', 'currency' => 'USD'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSame('currency', $result['violations'][0]['propertyPath']);
    }

    /**
     * A client that never set a currency explicitly has a null `currencyCode` column, and an
     * unfrozen invoice falls through to the client, which in turn falls through to the
     * company's default currency (USD). This must resolve to a currency, not 422.
     */
    public function testRecordPaymentResolvesTheCompanyDefaultCurrencyWhenTheClientNeverSetOne(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => null]);
        $invoice = InvoiceFactory::createOne(['status' => InvoiceStatus::Pending, 'client' => $client]);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $result = $this->postPayment($invoice, ['amount' => '10.00', 'currency' => 'USD'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertSame('USD', $result['currencyCode']);
    }

    /**
     * @return iterable<string, array{InvoiceStatus, string}>
     */
    public static function unpayableStatusProvider(): iterable
    {
        yield 'draft' => [InvoiceStatus::Draft, 'invoice_not_issued'];
        yield 'new' => [InvoiceStatus::New, 'invoice_not_issued'];
        yield 'paid' => [InvoiceStatus::Paid, 'invoice_not_payable'];
        yield 'cancelled' => [InvoiceStatus::Cancelled, 'invoice_not_payable'];
    }

    #[DataProvider('unpayableStatusProvider')]
    public function testAnInvoiceThatIsNotOpenCannotTakeAPayment(InvoiceStatus $status, string $type): void
    {
        $result = $this->postPayment($this->createInvoice('USD', ['status' => $status]), ['amount' => '10.00'])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertStringEndsWith('#' . $type, $result['type']);
    }

    public function testRecordPaymentForForeignCompanyInvoice(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $foreignClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne(['client' => $foreignClient, 'status' => InvoiceStatus::Pending]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->postPayment($foreignInvoice, ['amount' => '10.00']);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * A pending invoice with a total of 1000 in the major unit of $currency.
     *
     * @param array<string, mixed> $attributes
     */
    private function createInvoice(string $currency = 'USD', array $attributes = []): Invoice
    {
        $client = ClientFactory::createOne(['currencyCode' => $currency]);
        $price = match ($currency) {
            'JPY' => 1000,
            'BHD' => 1000000,
            default => 100000,
        };

        return InvoiceFactory::new([
            'status' => InvoiceStatus::Pending,
            'client' => $client,
            'paidDate' => null,
            'discount' => new Discount(),
            ...$attributes,
        ])
            ->afterInstantiate(static function (Invoice $invoice) use ($price): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice($price));
            })
            ->create();
    }

    private function giveCredit(Invoice $invoice, int $credit): void
    {
        $client = $invoice->getClient();
        self::assertInstanceOf(Client::class, $client);
        $client->getCredit()->setValue($credit);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }

    private function reload(Invoice $invoice): Invoice
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();

        $reloaded = $entityManager->find(Invoice::class, $invoice->getId());
        self::assertInstanceOf(Invoice::class, $reloaded);

        return $reloaded;
    }

    private function onlyPayment(Invoice $invoice): Payment
    {
        $payments = $this->reload($invoice)->getPayments();
        self::assertCount(1, $payments);
        $payment = $payments->first();
        self::assertInstanceOf(Payment::class, $payment);

        return $payment;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postPayment(Invoice $invoice, array $payload): ResponseInterface
    {
        return self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => $payload,
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);
    }
}
