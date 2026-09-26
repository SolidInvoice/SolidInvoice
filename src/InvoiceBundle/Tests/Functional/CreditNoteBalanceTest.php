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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\CreditNoteLine;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Listener\PaymentCompleteListener;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Guards the two cross-cutting risks the CN-3 design calls out explicitly:
 *
 *  - {@see PaymentCompleteListener} must route through
 *    {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator} rather than inlining the balance
 *    formula, or paying an invoice that carries a credit note silently restores the credited
 *    amount.
 *  - `CompanyFilter` scopes a credit note, but nothing stops a payload naming another company's
 *    invoice ULID on `invoice_id` without the
 *    {@see \SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompany}
 *    validator.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\CreditNoteWorkflowTest
 */
#[Group('functional')]
final class CreditNoteBalanceTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use EnsureApplicationInstalled;

    public function testPayingAnInvoiceWithACreditNoteDoesNotRestoreTheCreditedAmount(): void
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD', 'company' => $this->company]);

        // A Line, not a direct setTotal() call: InvoiceSaveListener recomputes `total` from the
        // line collection on every persist, and would zero out a directly-set total otherwise.
        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setStatus(InvoiceStatus::Pending);

        $line = new Line();
        $line->setQty(1);
        $line->setPrice(10000);

        $invoice->addLine($line);
        $this->em->persist($invoice);
        $this->em->flush();

        $creditNote = new CreditNote();
        $creditNote->setClient($client);
        $creditNote->setInvoice($invoice);

        $creditNoteLine = new CreditNoteLine();
        $creditNoteLine->setQty(1);
        $creditNoteLine->setPrice(3000);

        $creditNote->addLine($creditNoteLine);
        $this->em->persist($creditNote);
        $this->em->flush();

        /** @var WorkflowInterface $creditNoteWorkflow */
        $creditNoteWorkflow = self::getContainer()->get('state_machine.credit_note');
        $creditNoteWorkflow->apply($creditNote, Graph::TRANSITION_ISSUE);

        self::assertTrue(BigDecimal::of(7000)->isEqualTo($invoice->getBalance()), 'Sanity check: issuing the credit note reduces the balance by its amount.');

        $payment = new Payment();
        $payment->setTotalAmount(4000);
        $payment->setStatus(PaymentStatus::Captured);

        $invoice->addPayment($payment);
        $this->em->persist($invoice);
        $this->em->flush();

        /** @var PaymentCompleteListener $listener */
        $listener = self::getContainer()->get(PaymentCompleteListener::class);
        $listener->onPaymentComplete(new PaymentCompleteEvent($payment));

        // 10000 total - 4000 paid - 3000 credited = 3000. The pre-fix formula (total - paid only)
        // would give 6000, silently restoring the credited amount.
        self::assertTrue(
            BigDecimal::of(3000)->isEqualTo($invoice->getBalance()),
            'Paying an invoice that carries a credit note must not restore the credited amount.',
        );
    }

    public function testCreditNoteCannotReferenceAnInvoiceFromAnotherCompany(): void
    {
        $otherCompany = CompanyFactory::createOne(['name' => 'Another Company']);

        $filters = $this->em->getFilters();
        $filters->disable('company');

        $otherClient = ClientFactory::createOne(['company' => $otherCompany]);
        $foreignInvoice = InvoiceFactory::createOne(['company' => $otherCompany, 'client' => $otherClient]);

        $filters->enable('company');

        $client = ClientFactory::createOne(['company' => $this->company]);

        $creditNote = new CreditNote();
        $creditNote->setClient($client);
        $creditNote->setCompany($this->company);
        $creditNote->setInvoice($foreignInvoice);
        $creditNote->setTotal(1000);

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);
        $violations = $validator->validate($creditNote);

        self::assertGreaterThan(0, $violations->count());
        self::assertSame('invoice', $violations->get(0)->getPropertyPath());
    }
}
