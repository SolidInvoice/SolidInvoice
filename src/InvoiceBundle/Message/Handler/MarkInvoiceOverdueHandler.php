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

namespace SolidInvoice\InvoiceBundle\Message\Handler;

use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Message\MarkInvoiceOverdue;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Service\InvoiceStatusTransitionService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Message\Handler\MarkInvoiceOverdueHandlerTest
 */
#[AsMessageHandler]
final readonly class MarkInvoiceOverdueHandler
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private InvoiceStatusTransitionService $transitionService,
        private CompanySelector $companySelector,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(MarkInvoiceOverdue $message): void
    {
        // Switch to the invoice's company context
        $this->companySelector->switchCompany($message->getCompanyId());

        try {
            $invoice = $this->invoiceRepository->find($message->getInvoiceId());

            if (! $invoice instanceof Invoice) {
                $this->logger->warning('Invoice not found for overdue processing', [
                    'invoice_id' => $message->getInvoiceId()->toString(),
                    'company_id' => $message->getCompanyId()->toString(),
                ]);
                return;
            }

            // Idempotency check: only process if still pending
            if ($invoice->getStatus() !== InvoiceStatus::Pending) {
                $this->logger->info('Invoice no longer pending, skipping overdue processing', [
                    'invoice_id' => $invoice->getId()?->toString(),
                    'current_status' => $invoice->getStatus()?->value,
                ]);
                return;
            }

            // Apply overdue transition
            $this->transitionService->applyTransition(
                $invoice,
                Graph::TRANSITION_OVERDUE
            );

            $this->logger->info('Invoice marked as overdue', [
                'invoice_id' => $invoice->getInvoiceId(),
                'invoice_ulid' => $invoice->getId()->toString(),
                'client' => $invoice->getClient()?->getName(),
            ]);
        } catch (InvalidTransitionException $e) {
            $this->logger->error('Invalid transition when marking invoice overdue', [
                'invoice_id' => $message->getInvoiceId()->toString(),
                'exception' => $e,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Unexpected error marking invoice overdue', [
                'invoice_id' => $message->getInvoiceId()->toString(),
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw for message bus retry logic
        } finally {
            $this->companySelector->reset();
        }
    }
}
