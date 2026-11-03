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
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentMethodFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[Group('functional')]
final class VoidPaymentTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Payment::class;
    }

    public function testVoidingASettlingPaymentReopensThePaidInvoice(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $this->recordPayment($invoice, '400.00');
        $paymentId = $this->recordPayment($invoice, '600.00');
        self::assertSame(InvoiceStatus::Paid, $this->reload($invoice)->getStatus());

        $result = $this->void($paymentId)->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame('cancelled', $result['status']);

        $invoice = $this->reload($invoice);
        self::assertSame(InvoiceStatus::Pending, $invoice->getStatus());
        self::assertTrue(BigInteger::of(60000)->isEqualTo($invoice->getBalance()));
        self::assertNull($invoice->getPaidDate());
    }

    public function testVoidingAPaymentOnAPastDueInvoiceMakesItOverdue(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->subDays(5));
        $paymentId = $this->recordPayment($invoice, '1000.00');

        $this->void($paymentId);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertSame(InvoiceStatus::Overdue, $this->reload($invoice)->getStatus());
    }

    public function testVoidingAPartialPaymentRestoresTheBalance(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $paymentId = $this->recordPayment($invoice, '250.00');

        $this->void($paymentId);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        $invoice = $this->reload($invoice);
        self::assertSame(InvoiceStatus::Pending, $invoice->getStatus());
        self::assertTrue(BigInteger::of(100000)->isEqualTo($invoice->getBalance()));
    }

    public function testVoidingAnOverpaymentTakesBackTheCreditItAdded(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $paymentId = $this->recordPayment($invoice, '1200.00');
        self::assertTrue(BigInteger::of(20000)->isEqualTo($this->client($invoice)->getCredit()->getValue()));

        $this->void($paymentId);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertTrue($this->client($invoice)->getCredit()->getValue()->isZero());
        self::assertSame(InvoiceStatus::Pending, $this->reload($invoice)->getStatus());
    }

    public function testAnOverpaymentWhoseCreditWasSpentCannotBeVoided(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $paymentId = $this->recordPayment($invoice, '1200.00');
        $this->client($invoice)->getCredit()->setValue(0);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $result = $this->void($paymentId)->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertStringEndsWith('#payment_not_voidable', $result['type']);
    }

    public function testVoidingACreditPaymentRefundsTheCredit(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $this->client($invoice)->getCredit()->setValue(30000);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        $paymentId = $this->recordPayment($invoice, '100.00', 'credit');

        $this->void($paymentId);

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertTrue(BigInteger::of(30000)->isEqualTo($this->client($invoice)->getCredit()->getValue()));
    }

    public function testAPaymentOlderThanThirtyDaysCannotBeVoided(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $paymentId = $this->recordPayment($invoice, '100.00', paidAt: CarbonImmutable::now()->subDays(31)->toDateString());

        $result = $this->void($paymentId)->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertStringEndsWith('#payment_not_voidable', $result['type']);
    }

    public function testAnOnlineGatewayPaymentCannotBeVoided(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $payment = PaymentFactory::createOne([
            'invoice' => $invoice,
            'client' => $invoice->getClient(),
            'method' => PaymentMethodFactory::new(['factoryName' => 'stripe_checkout', 'internal' => false, 'enabled' => true]),
            'status' => PaymentStatus::Captured,
            'completed' => CarbonImmutable::now(),
        ]);

        $result = $this->void((string) $payment->getId())->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertStringEndsWith('#payment_not_voidable', $result['type']);
    }

    public function testAVoidedPaymentCannotBeVoidedAgain(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10));
        $paymentId = $this->recordPayment($invoice, '100.00');
        $this->void($paymentId);

        $this->void($paymentId);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testAnotherCompanysPaymentCannotBeVoided(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $payment = PaymentFactory::createOne([
            'client' => ClientFactory::new(['company' => $otherCompany]),
            'status' => PaymentStatus::Captured,
            'completed' => CarbonImmutable::now(),
        ]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $this->void((string) $payment->getId());

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUnpayIsNotAnInvoiceTransitionOfItsOwn(): void
    {
        $invoice = $this->createInvoice(CarbonImmutable::now()->addDays(10), InvoiceStatus::Paid);

        self::$client->request('POST', sprintf('/api/invoices/%s/transitions/%s', $invoice->getId(), Graph::TRANSITION_UNPAY), [
            'json' => [],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        // The web transition action and the MCP tools check the same can().
        $workflow = self::getContainer()->get('state_machine.invoice');
        self::assertInstanceOf(WorkflowInterface::class, $workflow);
        self::assertFalse($workflow->can($this->reload($invoice), Graph::TRANSITION_UNPAY));
        self::assertSame(InvoiceStatus::Paid, $this->reload($invoice)->getStatus());
    }

    private function createInvoice(CarbonImmutable $due, InvoiceStatus $status = InvoiceStatus::Pending): Invoice
    {
        return InvoiceFactory::new([
            'status' => $status,
            'client' => ClientFactory::new(['currencyCode' => 'USD']),
            'paidDate' => null,
            'due' => $due,
            'discount' => new Discount(),
        ])
            ->afterInstantiate(static function (Invoice $invoice): void {
                $invoice->addLine(new Line()->setQty(1)->setPrice(100000));
            })
            ->create();
    }

    private function recordPayment(Invoice $invoice, string $amount, string $method = 'cash', ?string $paidAt = null): string
    {
        $result = self::$client->request('POST', $this->getIriFromResource($invoice) . '/payments', [
            'json' => ['amount' => $amount, 'method' => $method, 'paidAt' => $paidAt],
            'headers' => ['content-type' => 'application/ld+json', 'accept' => 'application/ld+json'],
        ])->toArray(false);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        return $result['id'];
    }

    private function void(string $paymentId): ResponseInterface
    {
        return self::$client->request('POST', sprintf('/api/payments/%s/void', Ulid::fromString($paymentId)), [
            'headers' => ['accept' => 'application/ld+json'],
        ]);
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

    private function client(Invoice $invoice): Client
    {
        $client = $this->reload($invoice)->getClient();
        self::assertInstanceOf(Client::class, $client);

        return $client;
    }
}
