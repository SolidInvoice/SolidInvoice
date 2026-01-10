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
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
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
     * @return array{recurring: bool, form: FormView, invoice: Invoice}|Response
     * @throws MathException
     */
    #[Template('@SolidInvoiceInvoice/Default/edit.html.twig')]
    public function __invoke(Request $request, Invoice $invoice): array | Response
    {
        if (Graph::STATUS_PAID === $invoice->getStatus()) {
            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('warning', 'invoice.edit.paid');

            return new RedirectResponse($this->router->generate('_invoices_index'));
        }

        $client = $invoice->getClient();
        if (null === $client) {
            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('danger', 'invoice.edit.no_client');

            return new RedirectResponse($this->router->generate('_invoices_index'));
        }

        $form = $this->formFactory->create(InvoiceType::class, $invoice, [
            'currency' => $client->getCurrency(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            // Publish the invoice if the action is 'send' or 'publish'
            if ('send' === $action || 'publish' === $action) {
                $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
            }

            $this->doctrine->getManager()->flush();

            // Send the invoice only if the action is 'send'
            if ('send' === $action) {
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

        return [
            'recurring' => false,
            'form' => $form->createView(),
            'invoice' => $invoice,
        ];
    }
}
