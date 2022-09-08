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

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Exception\InvalidTransitionException;
use SolidInvoice\QuoteBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\StateMachine;

final class Transition
{
    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(StateMachine $stateMachine, RouterInterface $router)
    {
        $this->stateMachine = $stateMachine;
        $this->router = $router;
    }

    public function __invoke(Request $request, string $action, Quote $quote)
    {
        if (!$this->stateMachine->can($quote, $action)) {
            throw new InvalidTransitionException($action);
        }

        /** @var Marking $marking */
        $marking = $this->stateMachine->apply($quote, $action);

        $route = $this->router->generate('_quotes_view', ['id' => $quote->getId()]);

        if ($marking->has(Graph::STATUS_ACCEPTED)) {
            $route = $this->router->generate('_invoices_view', ['id' => $quote->getInvoice()->getId()]);
        }

        return new class($action, $route) extends RedirectResponse implements FlashResponse {
            /**
             * @var string
             */
            private $action;

            public function __construct(string $action, string $route)
            {
                $this->action = $action;
                parent::__construct($route);
            }

            public function getFlash(): iterable
            {
                yield self::FLASH_SUCCESS => 'quote.transition.action.' . $this->action;
            }
        };
    }
}
