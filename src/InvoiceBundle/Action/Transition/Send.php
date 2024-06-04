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

namespace SolidInvoice\InvoiceBundle\Action\Transition;

use Generator;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class Send
{
    use SaveableTrait;

    public function __construct(
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router
    ) {
    }

    public function __invoke(Request $request, Invoice $invoice): RedirectResponse
    {
        if (Graph::STATUS_PENDING !== $invoice->getStatus() && $this->invoiceStateMachine->can($invoice, Graph::TRANSITION_ACCEPT)) {
            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
        }

        $this->save($invoice);

        $this->mailer->send(new InvoiceEmail($invoice));

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.transition.action.sent';
            }
        };
    }
}
