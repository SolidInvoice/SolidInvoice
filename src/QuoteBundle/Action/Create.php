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

namespace SolidInvoice\QuoteBundle\Action;

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

final class Create extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $repository,
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $quoteStateMachine,
        private readonly ManagerRegistry $doctrine,
        private readonly TotalCalculator $totalCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    public function __invoke(Request $request, ?Client $client = null): Response
    {
        $totalClientsCount = $this->repository->getTotalClients();
        if (0 === $totalClientsCount) {
            return $this->render('@SolidInvoiceQuote/Default/empty_clients.html.twig');
        }

        $quote = new Quote();
        $quote->setClient($client);
        $quote->addLine(new Line());

        if (1 === $totalClientsCount && ! $client instanceof Client) {
            $client = $this->repository->findOneBy([]);
            $quote->setClient($client);
        }

        // Auto-select all client contacts
        if ($client instanceof Client) {
            foreach ($client->getContacts() as $contact) {
                $quote->addUser($contact);
            }
        }

        $formOptions = $client instanceof Client ? ['currency' => $client->getCurrency()] : [];
        $form = $this->createForm(QuoteType::class, $quote, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            if (! $quote->getId() instanceof Ulid) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_NEW);
            }

            if (Graph::STATUS_PENDING === $action) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND);
            }

            if ('publish' === $action) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_PUBLISH);
            }

            $entityManager = $this->doctrine->getManager();
            $entityManager->persist($quote);
            $entityManager->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'quote.action.create.success');

            return new RedirectResponse($this->router->generate('_quotes_view', ['id' => $quote->getId()]));
        }

        if ($form->isSubmitted() && ! $form->isValid()) {
            $this->totalCalculator->calculateTotals($quote);
        }

        return $this->render('@SolidInvoiceQuote/Default/create.html.twig', [
            'quote' => $quote,
            'form' => $form,
        ]);
    }
}
