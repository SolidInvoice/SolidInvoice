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
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class SendManualReminder
{
    public function __construct(
        private MailerInterface $mailer,
        private RouterInterface $router,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(Invoice $invoice): RedirectResponse
    {
        // Check if invoice has contacts to send to
        if ($invoice->getUsers()->isEmpty()) {
            return $this->createErrorResponse($invoice, 'invoice.manual_reminder.error.no_contacts');
        }

        // Send manual reminder email
        try {
            $this->mailer->send(new ManualInvoiceReminderEmail($invoice));

            $this->logger->info('Manual reminder sent for invoice', [
                'invoice_id' => $invoice->getInvoiceId(),
                'company_id' => $invoice->getCompany()->getId()->toRfc4122(),
            ]);

            return $this->createSuccessResponse($invoice);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send manual reminder', [
                'invoice_id' => $invoice->getInvoiceId(),
                'company_id' => $invoice->getCompany()->getId()->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);

            return $this->createErrorResponse($invoice, 'invoice.manual_reminder.error.send_failed');
        }
    }

    private function createSuccessResponse(Invoice $invoice): RedirectResponse
    {
        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        return new class($route) extends RedirectResponse implements FlashResponse {
            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_SUCCESS => 'invoice.manual_reminder.success';
            }
        };
    }

    private function createErrorResponse(Invoice $invoice, string $messageKey): RedirectResponse
    {
        $route = $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);

        return new class($route, $messageKey) extends RedirectResponse implements FlashResponse {
            public function __construct(
                string $url,
                private readonly string $messageKey,
            ) {
                parent::__construct($url);
            }

            public function getFlash(): Generator
            {
                yield FlashResponse::FLASH_ERROR => $this->messageKey;
            }
        };
    }
}
