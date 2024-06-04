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

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class Transition
{
    public function __construct(
        private readonly WorkflowInterface $quoteStateMachine,
        private readonly RouterInterface $router
    ) {
    }

    /**
     * @throws InvalidTransitionException
     */
    public function __invoke(Request $request, string $action, Quote $quote): RedirectResponse
    {
        if (! $this->quoteStateMachine->can($quote, $action)) {
            throw new InvalidTransitionException($action);
        }

        $marking = $this->quoteStateMachine->apply($quote, $action);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        if ($marking->has(Graph::STATUS_ACCEPTED)) {
            $route = $this->router->generate('_invoices_view', ['id' => $quote->getInvoice()->getId()]);
        }

        return new class($action, $route) extends RedirectResponse implements FlashResponse {
            public function __construct(
                private readonly string $action,
                string $route
            ) {
                parent::__construct($route);
            }

            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.' . $this->action;
            }
        };
    }
}
