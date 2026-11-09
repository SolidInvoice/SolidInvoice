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

use Brick\Math\BigDecimal;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Mcp\ClientWriteTools;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Mcp\InvoiceWriteTools;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\McpBundle\Security\McpOAuthAuthenticator;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Mcp\PaymentWriteTools;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Mcp\TaxReadTools;
use SolidInvoice\TaxBundle\Mcp\TaxWriteTools;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Uid\Ulid;

/**
 * Phase 3 write tool coverage — asserts scope enforcement, company binding,
 * and cross-tenant rejection for the write tools.
 */
#[Group('functional')]
final class WriteToolFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    public function testReadOnlyTokenRejectsWrite(): void
    {
        $this->setActiveScopes([McpScope::Read->value]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('mcp:write');

        $this->tool(ClientWriteTools::class)->createClient('Rejected', 'USD');
    }

    public function testClientCreateUpdateDeleteRoundTrip(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $tool = $this->tool(ClientWriteTools::class);

        $created = $tool->createClient('Acme Corp', 'usd', 'https://acme.example', 'inactive');
        self::assertSame('Acme Corp', $created->name);
        self::assertSame('USD', $created->currency);
        self::assertSame(ClientStatus::Inactive, $created->status);
        self::assertNotNull($created->id);

        // Bound to the token's company, never one supplied by the caller.
        $client = $this->repository(Client::class)->find(Ulid::fromString($created->id));
        self::assertInstanceOf(Client::class, $client);
        self::assertSame($this->company->getId()->toBase58(), $client->getCompany()->getId()->toBase58());

        $updated = $tool->updateClient($created->id, name: 'Acme Ltd', currency_code: 'EUR');
        self::assertSame('Acme Ltd', $updated->name);
        self::assertSame('EUR', $updated->currency);
        self::assertSame(ClientStatus::Inactive, $updated->status, 'Fields left out are unchanged.');

        $deleted = $tool->deleteClient($created->id);
        self::assertTrue($deleted->deleted);
        self::assertSame($created->id, $deleted->id);
        self::assertNull($this->repository(Client::class)->find(Ulid::fromString($created->id)));
    }

    public function testCreateClientWithoutCurrencyFollowsTheSystemDefault(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        self::assertSame('USD', $this->tool(ClientWriteTools::class)->createClient('No Currency')->currency);
    }

    public function testCreateClientRejectsInvalidFields(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Validation failed');

        $this->tool(ClientWriteTools::class)->createClient('Bad', 'NOPE');
    }

    public function testUpdateClientRejectsAnUnknownStatus(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('Invalid status');

        $this->tool(ClientWriteTools::class)->updateClient($client->getId()->toBase58(), status: 'gone');
    }

    public function testContactCreateUpdateDeleteRoundTrip(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $tool = $this->tool(ClientWriteTools::class);

        $created = $tool->createContact($client->getId()->toBase58(), 'new@example.com', 'Jane', 'Doe');
        self::assertSame('new@example.com', $created->email);
        self::assertSame('Jane', $created->first_name);
        self::assertSame('Doe', $created->last_name);
        self::assertNotNull($created->id);

        $contact = $this->repository(Contact::class)->find(Ulid::fromString($created->id));
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame($client->getId()->toBase58(), $contact->getClient()?->getId()?->toBase58());

        $updated = $tool->updateContact($created->id, last_name: 'Smith', email: 'jane@example.com');
        self::assertSame('Jane', $updated->first_name);
        self::assertSame('Smith', $updated->last_name);
        self::assertSame('jane@example.com', $updated->email);

        self::assertTrue($tool->deleteContact($created->id)->deleted);
        self::assertNull($this->repository(Contact::class)->find(Ulid::fromString($created->id)));
    }

    public function testCreateContactRejectsInvalidEmail(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('email');

        $this->tool(ClientWriteTools::class)->createContact($client->getId()->toBase58(), 'not-an-email', 'Jane');
    }

    public function testTaxRateCreateUpdateDeleteRoundTrip(): void
    {
        $this->setActiveScopes([McpScope::Write->value, McpScope::Read->value]);

        $tool = $this->tool(TaxWriteTools::class);

        $created = $tool->createTaxRate('VAT', 15.0, Tax::TYPE_EXCLUSIVE);
        $updated = $tool->updateTaxRate($created->id, rate: 20.0);
        self::assertSame($created->id, $updated->id);
        self::assertSame('VAT', $this->tool(TaxReadTools::class)->getTaxRate($created->id)->name);

        $deleted = $tool->deleteTaxRate($created->id);
        self::assertTrue($deleted->deleted);
        self::assertNull($this->repository(Tax::class)->find(Ulid::fromString($created->id)));
    }

    public function testDeleteReportsAMissingIdAsNotFound(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not found');

        $this->tool(TaxWriteTools::class)->deleteTaxRate(new Ulid()->toBase58());
    }

    public function testInvoiceTransitionRejectsInvalidTransition(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company]);
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Paid,
        ]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not enabled');

        // "pay" on an already-paid invoice should be rejected.
        $tool->applyInvoiceTransition($invoice->getId()->toBase58(), 'pay');
    }

    /**
     * A partial payment leaves the invoice pending with a reduced balance; the payment that settles
     * it marks it paid, and an overpayment's excess is reported as credit.
     */
    public function testRecordPaymentOnlyMarksTheInvoicePaidOnceSettled(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $invoice = InvoiceFactory::new([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'paidDate' => null,
            'discount' => new Discount(),
        ])
            ->afterInstantiate(static function (Invoice $invoice): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice(100000));
            })
            ->create();

        $tool = self::getContainer()->get(PaymentWriteTools::class);
        self::assertInstanceOf(PaymentWriteTools::class, $tool);

        $result = $tool->recordPayment($invoice->getId()->toBase58(), '0.01', 'USD', method: 'bank_transfer');

        self::assertSame(InvoiceStatus::Pending, $invoice->getStatus());
        self::assertTrue(BigDecimal::of(99999)->isEqualTo($invoice->getBalance()));
        self::assertSame('0.00', $result['credit_added']);

        $result = $tool->recordPayment($invoice->getId()->toBase58(), '1009.99', 'USD');

        self::assertSame(InvoiceStatus::Paid, $invoice->getStatus());
        self::assertSame('10.00', $result['credit_added']);
    }

    public function testRecordPaymentRejectsADraftInvoice(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']),
            'status' => InvoiceStatus::Draft,
        ]);

        $tool = self::getContainer()->get(PaymentWriteTools::class);
        self::assertInstanceOf(PaymentWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not been issued');

        $tool->recordPayment($invoice->getId()->toBase58(), '1.00', 'USD');
    }

    public function testUnpayIsNotAnInvoiceTransitionOfItsOwn(): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => ClientFactory::createOne(['company' => $this->company]),
            'status' => InvoiceStatus::Paid,
        ]);

        $tool = self::getContainer()->get(InvoiceWriteTools::class);
        self::assertInstanceOf(InvoiceWriteTools::class, $tool);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains('not enabled');

        $tool->applyInvoiceTransition($invoice->getId()->toBase58(), 'unpay');
    }

    /**
     * A decimal amount is read at the invoice currency's own scale and echoed back unchanged.
     */
    #[DataProvider('decimalAmounts')]
    public function testRecordPaymentReadsADecimalAmountInTheInvoiceCurrency(string $currency, string $amount, int $minorUnits): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $invoice = $this->payableInvoice($currency, 1_000_000);

        $result = $this->tool(PaymentWriteTools::class)->recordPayment($invoice->getId()->toBase58(), $amount, $currency);

        self::assertSame($amount, $result['amount']);
        self::assertSame($currency, $result['currency']);

        $payment = $this->repository(Payment::class)->find(Ulid::fromString((string) $result['id']));
        self::assertInstanceOf(Payment::class, $payment);
        self::assertSame((string) $minorUnits, $payment->getAmount()->getAmount());
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function decimalAmounts(): iterable
    {
        yield 'USD, 2 decimals' => ['USD', '12.50', 1250];
        yield 'JPY, no decimals' => ['JPY', '1500', 1500];
        yield 'BHD, 3 decimals' => ['BHD', '1.234', 1234];
    }

    #[DataProvider('invalidAmounts')]
    public function testRecordPaymentRejectsAnAmountThatIsNotAValidDecimal(string $currency, string $amount, string $message): void
    {
        $this->setActiveScopes([McpScope::Write->value]);

        $invoice = $this->payableInvoice($currency, 1_000_000);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $this->tool(PaymentWriteTools::class)->recordPayment($invoice->getId()->toBase58(), $amount, $currency);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidAmounts(): iterable
    {
        yield 'not a number' => ['USD', 'abc', 'not a non-negative decimal'];
        yield 'negative' => ['USD', '-1', 'not a non-negative decimal'];
        yield 'exponent' => ['USD', '1e3', 'not a non-negative decimal'];
        yield 'more decimals than USD has' => ['USD', '12.345', 'more decimal places than USD allows (2)'];
        yield 'any decimals for JPY' => ['JPY', '15.5', 'more decimal places than JPY allows (0)'];
    }

    private function payableInvoice(string $currency, int $priceInMinorUnits): Invoice
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => $currency]);

        return InvoiceFactory::new([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'paidDate' => null,
            'discount' => new Discount(),
        ])
            ->afterInstantiate(static function (Invoice $invoice) use ($priceInMinorUnits): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice($priceInMinorUnits));
            })
            ->create();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function tool(string $class): object
    {
        $tool = self::getContainer()->get($class);
        self::assertInstanceOf($class, $tool);

        return $tool;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return ObjectRepository<T>
     */
    private function repository(string $class): ObjectRepository
    {
        $doctrine = self::getContainer()->get('doctrine');
        self::assertInstanceOf(ManagerRegistry::class, $doctrine);

        return $doctrine->getRepository($class);
    }

    /**
     * @param list<string> $scopes
     */
    private function setActiveScopes(array $scopes): void
    {
        $container = self::getContainer();

        $stack = $container->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);

        while ($stack->getMainRequest() instanceof Request) {
            $stack->pop();
        }

        $request = new Request();
        $request->attributes->set(McpOAuthAuthenticator::ATTR_SCOPES, $scopes);

        $stack->push($request);

        $selector = $container->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);
        $selector->switchCompany($this->company->getId());
    }
}
