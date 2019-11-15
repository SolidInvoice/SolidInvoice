<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Action\Transition;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\MailerBundle\Mailer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\StateMachine;

final class Send
{
    use SaveableTrait;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(StateMachine $stateMachine, Mailer $mailer, RouterInterface $router)
    {
        $this->stateMachine = $stateMachine;
        $this->mailer = $mailer;
        $this->router = $router;
    }

    public function __invoke(Request $request, Invoice $invoice)
    {
        if (Graph::STATUS_PENDING !== $invoice->getStatus() && $this->stateMachine->can($invoice, Graph::TRANSITION_ACCEPT)) {
            $this->stateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
        }

        $this->save($invoice);

        $this->mailer->send(new InvoiceEmail($invoice));

        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse
        {
            public function getFlash(): iterable
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.transition.action.sent';
            }
        };
    }
}
