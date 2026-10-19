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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Draft-only, per `design` §7 on SOL-69 — the same rule {@see Edit} enforces.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\CreditNote\DeleteTest
 */
final readonly class Delete
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private RouterInterface $router,
    ) {
    }

    public function __invoke(CreditNote $creditNote, Request $request, Session $session): Response
    {
        $token = $request->request->get('_token');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken('delete' . $creditNote->getId(), $token))) {
            $session->getFlashBag()->add('danger', 'credit_note.delete.invalid_token');

            return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
        }

        if (CreditNoteStatus::Draft !== $creditNote->getStatus()) {
            $session->getFlashBag()->add('warning', 'credit_note.delete.not_draft');

            return new RedirectResponse($this->router->generate('_credit_notes_view', ['id' => $creditNote->getId()]));
        }

        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($creditNote);
        $entityManager->flush();

        $session->getFlashBag()->add('success', 'credit_note.delete.success');

        return new RedirectResponse($this->router->generate('_credit_notes_index'));
    }
}
