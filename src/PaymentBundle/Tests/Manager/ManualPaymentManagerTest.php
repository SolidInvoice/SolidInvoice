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

namespace SolidInvoice\PaymentBundle\Tests\Manager;

use Brick\Math\BigInteger;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentConflict;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Exception\InvalidPaymentException;
use SolidInvoice\PaymentBundle\Exception\PaymentConflictException;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ManualPaymentManagerTest extends TestCase
{
    private PaymentMethodRepository & Stub $methods;

    private EntityManagerInterface & Stub $entityManager;

    private WorkflowInterface & Stub $workflow;

    protected function setUp(): void
    {
        $this->methods = $this->createStub(PaymentMethodRepository::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->workflow = $this->createStub(WorkflowInterface::class);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
    }

    /**
     * @return iterable<string, array{InvoiceStatus, PaymentConflict}>
     */
    public static function closedInvoiceProvider(): iterable
    {
        yield 'draft' => [InvoiceStatus::Draft, PaymentConflict::InvoiceNotIssued];
        yield 'new' => [InvoiceStatus::New, PaymentConflict::InvoiceNotIssued];
        yield 'paid' => [InvoiceStatus::Paid, PaymentConflict::InvoiceNotPayable];
        yield 'archived' => [InvoiceStatus::Archived, PaymentConflict::InvoiceNotPayable];
    }

    #[DataProvider('closedInvoiceProvider')]
    public function testOnlyAnOpenInvoiceTakesAPayment(InvoiceStatus $status, PaymentConflict $conflict): void
    {
        try {
            $this->manager()->record($this->invoice($status), BigInteger::of(100));
            self::fail('Expected a conflict');
        } catch (PaymentConflictException $exception) {
            self::assertSame($conflict, $exception->conflict);
            self::assertSame(409, $exception->getStatus());
        }
    }

    public function testTheAmountMustBePositive(): void
    {
        $this->expectExceptionObject(new InvalidPaymentException('amount', 'payment.record.error.amount_not_positive'));

        $this->manager()->record($this->invoice(), BigInteger::zero());
    }

    public function testWithoutCashTheFirstOtherOfflineMethodIsUsedButNeverCredit(): void
    {
        $this->methods->method('findOneBy')->willReturn(null);
        $this->methods->method('findBy')->willReturn([
            $this->method('credit', 'offline'),
            $this->method('stripe_checkout', 'stripe_checkout'),
            $this->method('cheque', 'offline'),
        ]);

        $payment = $this->manager()->record($this->invoice(), BigInteger::of(100));

        self::assertSame('cheque', $payment->getMethod()?->getGatewayName());
        self::assertSame(PaymentStatus::Captured, $payment->getStatus());
        self::assertSame(100, $payment->getTotalAmount());
        self::assertSame('USD', $payment->getCurrencyCode());
    }

    public function testAPaymentDateBeforeTheEarliestTimeZoneReachesItIsAccepted(): void
    {
        CarbonImmutable::setTestNow('2026-10-27 12:00:00 UTC');
        $this->methods->method('findOneBy')->willReturn($this->method('cash', 'offline'));

        // Already the 28th in Kiribati.
        $payment = $this->manager()->record($this->invoice(), BigInteger::of(100), paidAt: new CarbonImmutable('2026-10-28', 'UTC'));

        self::assertSame('2026-10-28', $payment->getCompleted()?->format('Y-m-d'));
    }

    public function testUnpayIsBlockedOutsideAVoid(): void
    {
        $event = new GuardEvent(new Invoice(), new Marking(), new Transition(Graph::TRANSITION_UNPAY, 'paid', 'pending'));

        $this->manager()->guardUnpay($event);

        self::assertTrue($event->isBlocked());
    }

    public function testVoidingReopensAPastDueInvoiceWithoutRenotifying(): void
    {
        $invoice = $this->invoice(InvoiceStatus::Paid)->setDue(CarbonImmutable::now()->subDays(3));
        $invoice->setTotal(1000);

        $payments = $this->createStub(PaymentRepository::class);
        $payments->method('getTotalPaidForInvoice')->willReturn(BigInteger::of(1000));
        $this->entityManager->method('getRepository')->willReturn($payments);

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects($this->exactly(2))
            ->method('apply')
            ->with(
                $invoice,
                self::callback(static fn (string $transition): bool => in_array($transition, [Graph::TRANSITION_UNPAY, Graph::TRANSITION_OVERDUE], true)),
                [Workflow::DISABLE_ENTERED_EVENT => true],
            )
            ->willReturn(new Marking());
        $this->workflow = $workflow;

        $payment = $this->payment($invoice, 1000);

        $this->manager()->void($payment);

        self::assertSame(PaymentStatus::Cancelled, $payment->getStatus());
        self::assertTrue(BigInteger::of(1000)->isEqualTo($invoice->getBalance()));
        self::assertNull($invoice->getPaidDate());
    }

    public function testAPaymentPastTheVoidWindowIsKept(): void
    {
        $payment = $this->payment($this->invoice(), 100)->setCompleted(CarbonImmutable::now()->subDays(ManualPaymentManager::VOID_WINDOW_DAYS + 1));

        $this->expectException(PaymentConflictException::class);

        $this->manager()->void($payment);
    }

    private function manager(): ManualPaymentManager
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new ManualPaymentManager(
            $this->methods,
            $this->entityManager,
            $this->createStub(EventDispatcherInterface::class),
            $this->workflow,
            $translator,
        );
    }

    private function invoice(InvoiceStatus $status = InvoiceStatus::Pending): Invoice
    {
        $client = new Client();
        $client->setCurrencyCode('USD');

        $invoice = new Invoice();
        $invoice->setStatus($status);
        $invoice->setClient($client);
        $invoice->setCompany(new Company());
        $invoice->setInvoiceDate(CarbonImmutable::now());

        return $invoice;
    }

    private function method(string $gatewayName, string $factory): PaymentMethod
    {
        return new PaymentMethod()->setGatewayName($gatewayName)->setFactoryName($factory)->setInternal(false)->enable();
    }

    private function payment(Invoice $invoice, int $amount): Payment
    {
        $payment = new Payment();
        $payment->setMethod($this->method('cash', 'offline'));
        $payment->setStatus(PaymentStatus::Captured);
        $payment->setTotalAmount($amount);
        $payment->setCompleted(CarbonImmutable::now());

        $invoice->addPayment($payment);

        return $payment;
    }
}
