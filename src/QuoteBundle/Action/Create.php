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
use SolidInvoice\QuoteBundle\DTO\QuoteFormDTO;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteClientMode;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Manager\QuoteFormManager;
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
        private readonly QuoteFormManager $formManager,
    ) {
    }

    /**
     * @throws MathException
     */
    public function __invoke(Request $request, ?Client $client = null): Response
    {
        $totalClientsCount = $this->repository->getTotalClients();
        if (1 === $totalClientsCount && ! $client instanceof Client) {
            $client = $this->repository->findOneBy([]);
        }

        // Create DTO instead of entity
        $dto = new QuoteFormDTO();

        // Set client mode based on client count
        $dto->clientMode = $totalClientsCount > 0 ? QuoteClientMode::Existing : QuoteClientMode::NewClient;

        // Set client if provided
        $dto->client = $client;

        // Add one empty line item by default
        $dto->lines->add(new Line());

        // Contact auto-selection is handled by the LiveComponent's initializeContacts() hook

        $formOptions = $client instanceof Client ? ['currency' => $client->getCurrency()] : [];
        $form = $this->createForm(QuoteType::class, $dto, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            // Convert DTO to Quote entity
            // If clientMode=New, manager creates unpersisted client
            // Client will be persisted via cascade when quote is saved
            $quote = $this->formManager->createQuoteFromDTO($dto);

            if (! $quote->getId() instanceof Ulid) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_NEW);
            }

            // Send the quote (publish and notify client)
            if ('send' === $action) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND);
            }

            // Publish the quote (without sending)
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
            // Recalculate totals on validation failure
            try {
                $tempQuote = $this->formManager->createQuoteFromDTO($dto);
                $this->totalCalculator->calculateTotals($tempQuote);
                $dto->total = (string) $tempQuote->getTotal();
                $dto->baseTotal = (string) $tempQuote->getBaseTotal();
                $dto->tax = (string) $tempQuote->getTax();
            } catch (\InvalidArgumentException) {
                // Client data incomplete — keep DTO totals as-is
            }
        }

        return $this->render('@SolidInvoiceQuote/Default/create.html.twig', [
            'dto' => $dto,
            'form' => $form,
        ]);
    }
}
