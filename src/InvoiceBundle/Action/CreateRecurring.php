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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Form\Type\RecurringInvoiceType;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

final class CreateRecurring extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly WorkflowInterface $recurringInvoiceStateMachine,
        private readonly RouterInterface $router,
        private readonly ManagerRegistry $doctrine,
        private readonly TotalCalculator $totalCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    public function __invoke(Request $request, ?Client $client = null): Response
    {
        $totalClientsCount = $this->clientRepository->getTotalClients();
        if (0 === $totalClientsCount) {
            return $this->render('@SolidInvoiceInvoice/Default/empty_clients.html.twig');
        }
        if (1 === $totalClientsCount && ! $client instanceof Client) {
            $client = $this->clientRepository->findOneBy([]);
        }

        $invoice = new RecurringInvoice();
        $invoice->addLine(new RecurringInvoiceLine());
        $invoice->setClient($client);

        // Auto-select all client contacts
        if ($client instanceof Client) {
            foreach ($client->getContacts() as $contact) {
                $invoice->addUser($contact);
            }
        }

        $formOptions = $client instanceof Client ? ['currency' => $client->getCurrency()] : [];
        $form = $this->createForm(RecurringInvoiceType::class, $invoice, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if (! $invoice->getId() instanceof Ulid) {
                $this->recurringInvoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);
            }

            if ('publish' === $action) {
                $this->recurringInvoiceStateMachine->apply($invoice, Graph::TRANSITION_ACTIVATE);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($invoice);
            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'invoice.create.success');

            return new RedirectResponse($this->router->generate('_invoices_view_recurring', ['id' => $invoice->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($invoice);
        }

        return $this->render('@SolidInvoiceInvoice/Default/create.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
            'recurring' => true,
        ]);
    }
}
