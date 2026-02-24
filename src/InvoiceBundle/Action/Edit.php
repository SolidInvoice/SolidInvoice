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
use SolidInvoice\InvoiceBundle\DTO\InvoiceFormDTO;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Form\Type\InvoiceType;
use SolidInvoice\InvoiceBundle\Manager\InvoiceFormManager;
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
        private readonly InvoiceFormManager $formManager,
    ) {
    }

    /**
     * @return array{recurring: bool, form: FormView, dto: InvoiceFormDTO, invoice: Invoice}|Response
     * @throws MathException
     */
    #[Template('@SolidInvoiceInvoice/Default/edit.html.twig')]
    public function __invoke(Request $request, Invoice $invoice): array | Response
    {
        if (InvoiceStatus::Paid === $invoice->getStatus()) {
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

        // Convert Invoice entity to DTO for editing
        $dto = $this->formManager->createDTOFromInvoice($invoice);

        $form = $this->formFactory->create(InvoiceType::class, $dto, [
            'currency' => $client->getCurrency(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            // Update Invoice from DTO
            $this->formManager->updateInvoiceFromDTO($invoice, $dto);

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
            // Recalculate totals on validation failure
            try {
                $tempInvoice = $this->formManager->createInvoiceFromDTO($dto);
                $this->totalCalculator->calculateTotals($tempInvoice);
                $dto->total = (string) $tempInvoice->getTotal();
                $dto->baseTotal = (string) $tempInvoice->getBaseTotal();
                $dto->tax = (string) $tempInvoice->getTax();
            } catch (\InvalidArgumentException) {
                // Client data incomplete — keep DTO totals as-is
            }
        }

        return [
            'recurring' => false,
            'form' => $form->createView(),
            'dto' => $dto,
            'invoice' => $invoice,
        ];
    }
}
