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
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\CoreBundle\Traits\SaveableTrait;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Action\Transition\SendTest
 */
final class Send
{
    use SaveableTrait;

    public function __construct(
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly MailerInterface $mailer,
        private readonly RouterInterface $router,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request, Invoice $invoice): RedirectResponse
    {
        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        if ($this->emailVerificationGate->isGated()) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'email_verification.flash.send_invoice';
                }
            };
        }

        if ($invoice->getUsers()->isEmpty()) {
            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'invoice.send.no_recipients';
                }
            };
        }

        if (InvoiceStatus::Pending !== $invoice->getStatus() && $this->invoiceStateMachine->can($invoice, Graph::TRANSITION_ACCEPT)) {
            $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_ACCEPT);
        }

        $this->save($invoice);

        try {
            $this->mailer->send(new InvoiceEmail($invoice));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send invoice email: ' . $e->getMessage(), ['exception' => $e]);

            return new class($route) extends RedirectResponse implements FlashResponse {
                public function getFlash(): Generator
                {
                    yield FlashResponse::FLASH_ERROR => 'invoice.email.send_failed';
                }
            };
        }

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.transition.action.sent';
            }
        };
    }
}
