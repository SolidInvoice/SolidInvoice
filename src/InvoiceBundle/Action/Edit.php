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

namespace SolidInvoice\InvoiceBundle\Action;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

final class Edit
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly ManagerRegistry $doctrine,
        private readonly MailerInterface $mailer,
        private readonly TotalCalculator $totalCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    public function __invoke(Request $request, Invoice $invoice): Template | Response
    {
        if (Graph::STATUS_PAID === $invoice->getStatus()) {
            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('warning', 'invoice.edit.paid');

            return new RedirectResponse($this->router->generate('_invoices_index'));
        }

        $form = $this->formFactory->create(InvoiceType::class, $invoice, [
            'currency' => $invoice->getClient()->getCurrency(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if (! $invoice->getId() instanceof Ulid) {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);
            }

            if (Graph::STATUS_PENDING === $action || 'publish' === $action) {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
            }

            $this->doctrine->getManager()->flush();

            if (Graph::STATUS_PENDING === $action) {
                $this->mailer->send(new InvoiceEmail($invoice));
            }

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'invoice.edit.success');

            return new RedirectResponse($this->router->generate('_invoices_view', ['id' => $invoice->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($invoice);
        }

        return new Template(
            '@SolidInvoiceInvoice/Default/edit.html.twig',
            [
                'recurring' => false,
                'form' => $form->createView(),
                'invoice' => $invoice,
            ]
        );
    }
}
