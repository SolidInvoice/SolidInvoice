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

use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\TransitionTest
 */
final class Transition
{
    use SaveableTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $creditNoteStateMachine,
    ) {
    }

    public function __invoke(string $action, CreditNote $creditNote, Session $session): RedirectResponse
    {
        if (! $this->creditNoteStateMachine->can($creditNote, $action)) {
            throw new InvalidTransitionException($action);
        }

        $this->creditNoteStateMachine->apply($creditNote, $action);

        $this->save($creditNote);

        $session->getFlashBag()->add('success', 'credit_note.transition.action.' . $action);

        return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
    }
}
