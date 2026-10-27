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

namespace SolidInvoice\PaymentBundle\Manager;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentConflict;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Exception\InvalidPaymentException;
use SolidInvoice\PaymentBundle\Exception\PaymentConflictException;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Records and voids payments taken outside SolidInvoice (cash, bank transfer, credit, …) for every
 * entry point other than the web payment form, with the same outcome as that form's offline path
 * ({@see \SolidInvoice\PaymentBundle\Action\Prepare}): a captured payment and a
 * {@see PaymentEvents::PAYMENT_COMPLETE} event, so the invoice is only marked paid once settled and
 * an overpayment goes to client credit.
 *
 * @see \SolidInvoice\PaymentBundle\Tests\Manager\ManualPaymentManagerTest
 */
final class ManualPaymentManager
{
    public const string DEFAULT_METHOD = 'cash';

    public const int VOID_WINDOW_DAYS = 30;

    private const string CREDIT_METHOD = 'credit';

    /**
     * The earliest time zone. A date that has not started there has not started anywhere.
     */
    private const string EARLIEST_TIMEZONE = 'Pacific/Kiritimati';

    /**
     * The `unpay` transition is only allowed while a void is in progress; see guardUnpay().
     */
    private bool $voiding = false;

    public function __construct(
        private readonly PaymentMethodRepository $paymentMethodRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @param BigInteger $amount Minor units of the invoice currency
     * @param string|null $method Gateway name of an enabled offline or internal method, `cash` when null
     *
     * @throws InvalidPaymentException
     * @throws PaymentConflictException
     * @throws MathException
     */
    public function record(
        Invoice $invoice,
        BigInteger $amount,
        ?string $currency = null,
        ?string $method = null,
        ?DateTimeInterface $paidAt = null,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        $this->assertPayable($invoice);

        $client = $invoice->getClient();

        if (! $client instanceof Client) {
            throw $this->invalid('invoice', 'payment.record.error.no_client');
        }

        if (! $amount->isPositive()) {
            throw $this->invalid('amount', 'payment.record.error.amount_not_positive');
        }

        $invoiceCurrency = $invoice->getCurrency()->getCode();

        if ($currency !== null && strtoupper($currency) !== $invoiceCurrency) {
            throw $this->invalid('currency', 'payment.record.error.currency_mismatch', ['%currency%' => $currency, '%invoice_currency%' => $invoiceCurrency]);
        }

        $paymentMethod = $this->resolveMethod($method);
        $completed = $this->resolvePaidAt($invoice, $paidAt);

        // The same checks, and messages, as the web form.
        if ($paymentMethod->getGatewayName() === self::CREDIT_METHOD) {
            if ($amount->isGreaterThan($client->getCredit()->getValue())) {
                throw $this->invalid('amount', 'payment.create.exception.not_enough_credit');
            }

            if ($amount->isGreaterThan($invoice->getBalance())) {
                throw $this->invalid('amount', 'payment.create.exception.amount_exceeds_balance');
            }
        }

        $contact = $client->getContacts()->first();

        $payment = new Payment();
        $payment->setStatus(PaymentStatus::Captured);
        $payment->setMethod($paymentMethod);
        $payment->setTotalAmount($amount->toInt());
        $payment->setCurrencyCode($invoiceCurrency);
        $payment->setDescription('');
        $payment->setClient($client);
        $payment->setNumber($invoice->getId()?->toString());
        $payment->setClientEmail($contact instanceof Contact ? $contact->getEmail() : null);
        $payment->setReference($reference);
        $payment->setNotes($notes);
        $payment->setCompleted($completed);
        $payment->setCompany($invoice->getCompany());

        $invoice->addPayment($payment);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        // PaymentCompleteListener settles the invoice, and InvoicePaidListener credits any excess.
        $creditBefore = $client->getCredit()->getValue();
        $this->eventDispatcher->dispatch(new PaymentCompleteEvent($payment), PaymentEvents::PAYMENT_COMPLETE);
        $creditAdded = $client->getCredit()->getValue()->toBigDecimal()->minus($creditBefore);

        $payment->setCreditAdded($creditAdded->isPositive() ? $creditAdded : BigInteger::zero());

        return $payment;
    }

    /**
     * Cancels a manually recorded payment and reopens its invoice when that leaves it unpaid. Client
     * credit moves back with it: an overpayment's excess is taken back, a credit payment refunded.
     *
     * @throws PaymentConflictException
     * @throws MathException
     */
    public function void(Payment $payment): Payment
    {
        $method = $payment->getMethod();

        if (! $method instanceof PaymentMethod || $method->isOnline()) {
            throw $this->conflict(PaymentConflict::PaymentNotVoidable, 'payment.void.error.not_manual');
        }

        if ($payment->getStatus() !== PaymentStatus::Captured) {
            throw $this->conflict(PaymentConflict::PaymentNotVoidable, 'payment.void.error.not_captured');
        }

        $completed = $payment->getCompleted();

        if (! $completed instanceof DateTimeInterface || $completed < CarbonImmutable::now()->subDays(self::VOID_WINDOW_DAYS)) {
            throw $this->conflict(PaymentConflict::PaymentNotVoidable, 'payment.void.error.too_old', ['%days%' => self::VOID_WINDOW_DAYS]);
        }

        $invoice = $payment->getInvoice();
        $client = $payment->getClient();
        $amount = BigInteger::of($payment->getTotalAmount());

        $creditChange = BigInteger::zero();
        $wasPaid = false;
        $paidAfter = BigDecimal::zero();

        if ($invoice instanceof Invoice) {
            $total = $invoice->getTotal();
            $paidBefore = $this->paymentRepository()->getTotalPaidForInvoice($invoice);
            $paidAfter = $paidBefore->toBigDecimal()->minus($amount);
            $wasPaid = $invoice->getStatus() === InvoiceStatus::Paid;

            // InvoicePaidListener credited the excess when the invoice was marked paid.
            if ($wasPaid) {
                $creditChange = $creditChange->minus($this->excess($paidBefore, $total))->plus($this->excess($paidAfter, $total));
            }
        }

        if ($method->getGatewayName() === self::CREDIT_METHOD) {
            $creditChange = $creditChange->plus($amount);
        }

        if (! $creditChange->isZero()) {
            if (! $client instanceof Client || $client->getCredit()->getValue()->toBigDecimal()->plus($creditChange)->isNegative()) {
                throw $this->conflict(PaymentConflict::PaymentNotVoidable, 'payment.void.error.credit_spent');
            }

            $credit = $client->getCredit();
            $credit->setValue($credit->getValue()->toBigDecimal()->plus($creditChange));
        }

        $payment->setStatus(PaymentStatus::Cancelled);
        // Flushed on its own, so InvoiceSaveListener recomputes the balance without this payment.
        $this->entityManager->flush();

        if (! $invoice instanceof Invoice) {
            return $payment;
        }

        $invoice->setBalance($invoice->getTotal()->toBigDecimal()->minus($paidAfter));

        if ($wasPaid && $paidAfter->isLessThan($invoice->getTotal())) {
            $invoice->setPaidDate(null);

            // No "entered" events: the invoice is not newly pending or overdue as far as anyone who was
            // notified is concerned, so the status and overdue notifications must not go out again.
            $context = [Workflow::DISABLE_ENTERED_EVENT => true];

            $this->voiding = true;

            try {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_UNPAY, $context);
            } finally {
                $this->voiding = false;
            }

            $due = $invoice->getDue();

            if ($due instanceof DateTimeInterface && $due->format('Y-m-d') < CarbonImmutable::today()->toDateString()) {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_OVERDUE, $context);
            }
        }

        $this->entityManager->flush();

        return $payment;
    }

    /**
     * Keeps `unpay` out of every generic transition endpoint (web, REST and MCP all check can()):
     * reopening a paid invoice is a side effect of voiding a payment, never an action of its own.
     *
     * @param GuardEvent<Invoice> $event
     */
    #[AsEventListener('workflow.invoice.guard.' . Graph::TRANSITION_UNPAY)]
    public function guardUnpay(GuardEvent $event): void
    {
        if (! $this->voiding) {
            $event->setBlocked(true, 'Only voiding a payment reopens a paid invoice.');
        }
    }

    /**
     * @throws PaymentConflictException
     */
    private function assertPayable(Invoice $invoice): void
    {
        $status = $invoice->getStatus();

        if ($status === InvoiceStatus::Pending || $status === InvoiceStatus::Overdue) {
            return;
        }

        if (in_array($status, [null, InvoiceStatus::New, InvoiceStatus::Draft], true)) {
            throw $this->conflict(PaymentConflict::InvoiceNotIssued, 'payment.record.error.invoice_not_issued');
        }

        throw $this->conflict(PaymentConflict::InvoiceNotPayable, 'payment.record.error.invoice_not_payable', ['%status%' => $status->value]);
    }

    /**
     * @throws InvalidPaymentException
     */
    private function resolveMethod(?string $gatewayName): PaymentMethod
    {
        if ($gatewayName !== null) {
            $method = $this->paymentMethodRepository->findOneBy(['gatewayName' => $gatewayName]);

            if (! $method instanceof PaymentMethod || ! $method->isEnabled() || $method->isOnline()) {
                throw $this->invalid('method', 'payment.record.error.method_unavailable', ['%method%' => $gatewayName]);
            }

            return $method;
        }

        $default = $this->paymentMethodRepository->findOneBy(['gatewayName' => self::DEFAULT_METHOD]);

        if ($default instanceof PaymentMethod && $default->isEnabled() && ! $default->isOnline()) {
            return $default;
        }

        // Without cash, the first other usable method by name. Never credit: paying from the client's
        // credit has to be asked for.
        foreach ($this->paymentMethodRepository->findBy([], ['name' => 'ASC']) as $method) {
            if ($method->isEnabled() && ! $method->isOnline() && $method->getGatewayName() !== self::CREDIT_METHOD) {
                return $method;
            }
        }

        throw $this->invalid('method', 'payment.record.error.no_method');
    }

    /**
     * @throws InvalidPaymentException
     */
    private function resolvePaidAt(Invoice $invoice, ?DateTimeInterface $paidAt): CarbonImmutable
    {
        if (! $paidAt instanceof DateTimeInterface) {
            return CarbonImmutable::now();
        }

        $date = $paidAt->format('Y-m-d');

        if ($date > CarbonImmutable::now(self::EARLIEST_TIMEZONE)->toDateString()) {
            throw $this->invalid('paidAt', 'payment.record.error.paid_at_future');
        }

        if ($date < CarbonImmutable::instance($invoice->getInvoiceDate())->subYear()->toDateString()) {
            throw $this->invalid('paidAt', 'payment.record.error.paid_at_too_old');
        }

        return CarbonImmutable::instance($paidAt);
    }

    private function excess(BigNumber $paid, BigNumber $total): BigInteger
    {
        $excess = $paid->toBigDecimal()->minus($total)->toBigInteger();

        return $excess->isPositive() ? $excess : BigInteger::zero();
    }

    private function paymentRepository(): PaymentRepository
    {
        return $this->entityManager->getRepository(Payment::class);
    }

    /**
     * @param array<string, string|int> $parameters
     */
    private function invalid(string $field, string $key, array $parameters = []): InvalidPaymentException
    {
        return new InvalidPaymentException($field, $this->translator->trans($key, $parameters));
    }

    /**
     * @param array<string, string|int> $parameters
     */
    private function conflict(PaymentConflict $conflict, string $key, array $parameters = []): PaymentConflictException
    {
        return new PaymentConflictException($conflict, $this->translator->trans($key, $parameters));
    }
}
