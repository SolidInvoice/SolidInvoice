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
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\DTO\QuoteFormDTO;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Form\Type\QuoteType;
use SolidInvoice\QuoteBundle\Manager\QuoteFormManager;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function assert;

final class Edit
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $quoteStateMachine,
        private readonly ManagerRegistry $doctrine,
        private readonly TotalCalculator $totalCalculator,
        private readonly QuoteFormManager $formManager,
    ) {
    }

    /**
     * @return array{form: FormView, dto: QuoteFormDTO, quote: Quote}|Response
     * @throws MathException
     */
    #[Template('@SolidInvoiceQuote/Default/edit.html.twig')]
    public function __invoke(Request $request, Quote $quote): array | Response
    {
        $client = $quote->getClient();
        $formOptions = [];
        if (null !== $client) {
            $formOptions['currency'] = $client->getCurrency();
        }

        // Convert Quote entity to DTO for editing
        $dto = $this->formManager->createDTOFromQuote($quote);

        $form = $this->formFactory->create(QuoteType::class, $dto, $formOptions);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('save');

            // Update Quote from DTO
            $this->formManager->updateQuoteFromDTO($quote, $dto);

            // Send the quote (publish and notify client)
            if ('send' === $action) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_SEND);
            }

            // Publish the quote (without sending)
            if ('publish' === $action) {
                $this->quoteStateMachine->apply($quote, Graph::TRANSITION_PUBLISH);
            }

            $this->doctrine->getManager()->flush();

            $session = $request->getSession();
            assert($session instanceof Session);
            $session->getFlashBag()->add('success', 'quote.action.edit.success');

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

        return [
            'form' => $form->createView(),
            'dto' => $dto,
            'quote' => $quote,
        ];
    }
}
