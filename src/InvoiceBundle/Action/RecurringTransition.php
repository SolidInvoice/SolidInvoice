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
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class RecurringTransition
{
    use SaveableTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly WorkflowInterface $recurringInvoiceStateMachine
    ) {
    }

    public function __invoke(Request $request, string $action, RecurringInvoice $invoice)
    {
        if (! $this->recurringInvoiceStateMachine->can($invoice, $action)) {
            throw new InvalidTransitionException($action);
        }

        $this->recurringInvoiceStateMachine->apply($invoice, $action);

        $this->save($invoice);

        $route = $this->router->generate('_invoices_view_recurring', ['id' => $invoice->getId()]);

        return new class($action, $route) extends RedirectResponse implements FlashResponse {
            public function __construct(
                private readonly string $action,
                string $route
            ) {
                parent::__construct($route);
            }

            public function getFlash(): Generator
            {
                yield self::FLASH_SUCCESS => 'invoice.transition.action.' . $this->action;
            }
        };
    }
}
