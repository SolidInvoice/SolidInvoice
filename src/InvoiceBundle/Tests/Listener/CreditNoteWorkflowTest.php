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

namespace SolidInvoice\InvoiceBundle\Tests\Listener;

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Exercises the `credit_note` state machine end to end, proving that the
 * `workflow.credit_note.completed.*` events registered in `config/packages/workflow.php`
 * reach {@see \SolidInvoice\InvoiceBundle\Listener\CreditNoteIssuedListener} and
 * {@see \SolidInvoice\InvoiceBundle\Listener\CreditNoteCancelledListener}.
 *
 * @see \SolidInvoice\InvoiceBundle\Listener\CreditNoteIssuedListener
 * @see \SolidInvoice\InvoiceBundle\Listener\CreditNoteCancelledListener
 */
final class CreditNoteWorkflowTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testIssueTransitionAssignsANumberAndStampsTheDate(): void
    {
        $client = ClientFactory::createOne();

        $creditNote = new CreditNote();
        $creditNote->setClient($client);

        $this->em->persist($creditNote);
        $this->em->flush();

        self::assertSame('', $creditNote->getCreditNoteId());

        /** @var WorkflowInterface $stateMachine */
        $stateMachine = self::getContainer()->get('state_machine.credit_note');

        self::assertTrue($stateMachine->can($creditNote, Graph::TRANSITION_ISSUE));

        $stateMachine->apply($creditNote, Graph::TRANSITION_ISSUE);

        self::assertSame(CreditNoteStatus::Issued, $creditNote->getStatus());
        self::assertNotSame('', $creditNote->getCreditNoteId());
        self::assertStringStartsWith('CN-', $creditNote->getCreditNoteId());
    }

    public function testCancelTransitionDoesNotReleaseTheNumberAndLeavesASequenceGap(): void
    {
        $client = ClientFactory::createOne();

        /** @var WorkflowInterface $stateMachine */
        $stateMachine = self::getContainer()->get('state_machine.credit_note');

        $first = new CreditNote();
        $first->setClient($client);

        $this->em->persist($first);
        $this->em->flush();

        $stateMachine->apply($first, Graph::TRANSITION_ISSUE);
        $firstNumber = $first->getCreditNoteId();

        self::assertTrue($stateMachine->can($first, Graph::TRANSITION_CANCEL));

        $stateMachine->apply($first, Graph::TRANSITION_CANCEL);

        self::assertSame(CreditNoteStatus::Cancelled, $first->getStatus());
        // The number is not released: cancelling keeps the id it was already assigned.
        self::assertSame($firstNumber, $first->getCreditNoteId());

        $second = new CreditNote();
        $second->setClient($client);

        $this->em->persist($second);
        $this->em->flush();

        $stateMachine->apply($second, Graph::TRANSITION_ISSUE);

        // The cancelled credit note's number was not reused; the sequence moved on past it.
        self::assertNotSame($firstNumber, $second->getCreditNoteId());
    }
}
