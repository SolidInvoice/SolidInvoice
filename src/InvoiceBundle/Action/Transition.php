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

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\TransitionTest
 */
final class Transition
{
    use SaveableTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $invoiceStateMachine,
    ) {
    }

    public function __invoke(Request $request, string $action, Invoice $invoice): RedirectResponse
    {
        if (! $this->invoiceStateMachine->can($invoice, $action)) {
            throw new InvalidTransitionException($action);
        }

        $marking = $this->invoiceStateMachine->apply($invoice, $action);

        $this->save($invoice);

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        if ($marking->has(InvoiceStatus::Archived->value)) {
            $route = $this->router->generate('_invoices_index');
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
                // Cancelling ends the expectation of payment, so it does not get the
                // product's good-news bar. archive and every other transition keep it.
                $severity = Graph::TRANSITION_CANCEL === $this->action ? self::FLASH_INFO : self::FLASH_SUCCESS;

                yield $severity => 'invoice.transition.action.' . $this->action;
            }
        };
    }
}
