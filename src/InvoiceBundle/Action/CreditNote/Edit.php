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

namespace SolidInvoice\InvoiceBundle\Action\CreditNote;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Form\Type\CreditNoteType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

/**
 * Draft-only, per `design` §7 on SOL-69 — an issued or cancelled credit note is a closed
 * legal document.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\EditTest
 */
final readonly class Edit
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RouterInterface $router,
        private WorkflowInterface $creditNoteStateMachine,
        private ManagerRegistry $doctrine,
        private TotalCalculator $totalCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    #[Template('@SolidInvoiceInvoice/CreditNote/create.html.twig')]
    public function __invoke(Request $request, CreditNote $creditNote): array | Response
    {
        if (CreditNoteStatus::Draft !== $creditNote->getStatus()) {
            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('warning', 'credit_note.edit.not_draft');

            return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
        }

        $form = $this->formFactory->create(CreditNoteType::class, $creditNote, [
            'currency' => $creditNote->getCurrency(),
            'client_locked' => $creditNote->getInvoice() !== null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if ('issue' === $action && $this->creditNoteStateMachine->can($creditNote, Graph::TRANSITION_ISSUE)) {
                $this->creditNoteStateMachine->apply($creditNote, Graph::TRANSITION_ISSUE);
            }

            $this->doctrine->getManager()->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'credit_note.edit.success');

            return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($creditNote);
        }

        return [
            'form' => $form->createView(),
            'creditNote' => $creditNote,
            'isEdit' => true,
        ];
    }
}
