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

namespace SolidInvoice\InvoiceBundle\Mcp;

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

final class InvoiceWriteTools
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly RecurringInvoiceRepository $recurringInvoiceRepository,
        private readonly InvoiceCloner $cloner,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        #[Autowire(service: 'state_machine.invoice')]
        private readonly WorkflowInterface $invoiceWorkflow,
        #[Autowire(service: 'state_machine.recurring_invoice')]
        private readonly WorkflowInterface $recurringInvoiceWorkflow,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Apply a workflow transition to an invoice.
     *
     * @param string $invoice_id Invoice ULID
     * @param string $transition One of: accept, cancel, overdue, pay, reopen, archive
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'apply_invoice_transition', description: 'Apply a workflow transition (accept, cancel, overdue, pay, reopen, archive) to an invoice.')]
    #[McpScopeRequired(McpScope::Write)]
    public function applyInvoiceTransition(string $invoice_id, string $transition): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
        }

        if (! $this->invoiceWorkflow->can($invoice, $transition)) {
            throw new ToolCallException(sprintf(
                'Transition "%s" is not enabled for invoice in status "%s".',
                $transition,
                $invoice->getStatus()?->value ?? 'unknown',
            ));
        }

        $this->invoiceWorkflow->apply($invoice, $transition);
        $this->entityManager->flush();

        return $this->normalizer->normalize($invoice);
    }

    /**
     * Apply a workflow transition to a recurring invoice.
     *
     * @param string $recurring_id Recurring invoice ULID
     * @param string $transition   One of: activate, pause, resume, complete, cancel, archive
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'apply_recurring_transition', description: 'Apply a workflow transition to a recurring invoice.')]
    #[McpScopeRequired(McpScope::Write)]
    public function applyRecurringTransition(string $recurring_id, string $transition): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $recurring = $this->recurringInvoiceRepository->find(UlidParser::parse($recurring_id, 'recurring_id'));

        if (! $recurring instanceof RecurringInvoice) {
            throw new ToolCallException(sprintf('Recurring invoice %s not found.', $recurring_id));
        }

        if (! $this->recurringInvoiceWorkflow->can($recurring, $transition)) {
            throw new ToolCallException(sprintf(
                'Transition "%s" is not enabled for recurring invoice in status "%s".',
                $transition,
                $recurring->getStatus()?->value ?? 'unknown',
            ));
        }

        $this->recurringInvoiceWorkflow->apply($recurring, $transition);
        $this->entityManager->flush();

        return $this->normalizer->normalize($recurring);
    }

    /**
     * Clone an existing invoice into a new draft.
     *
     * @param string $invoice_id Invoice ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'clone_invoice', description: 'Clone an existing invoice into a new draft with the same line items.')]
    #[McpScopeRequired(McpScope::Write)]
    public function cloneInvoice(string $invoice_id): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
        }

        $cloned = $this->cloner->clone($invoice);

        return $this->normalizer->normalize($cloned);
    }

    /**
     * Send a manual reminder email to the contacts on an invoice.
     *
     * @param string $invoice_id Invoice ULID
     *
     * @return array{sent: bool, invoice_id: string}
     */
    #[McpTool(name: 'send_invoice_reminder', description: 'Email a manual reminder for an invoice to its contacts.')]
    #[McpScopeRequired(McpScope::Write)]
    public function sendInvoiceReminder(string $invoice_id): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $invoice = $this->invoiceRepository->find(UlidParser::parse($invoice_id, 'invoice_id'));

        if (! $invoice instanceof Invoice) {
            throw new ToolCallException(sprintf('Invoice %s not found.', $invoice_id));
        }

        if ($invoice->getUsers()->isEmpty()) {
            throw new ToolCallException('Invoice has no contacts to send a reminder to.');
        }

        try {
            $this->mailer->send(new ManualInvoiceReminderEmail($invoice));

            $this->logger->info('Manual reminder sent via MCP', [
                'invoice_id' => $invoice->getInvoiceId(),
                'company_id' => $invoice->getCompany()->getId()->toRfc4122(),
            ]);
        } catch (TransportExceptionInterface $exception) {
            throw new ToolCallException('Failed to send reminder: ' . $exception->getMessage());
        }

        return [
            'sent' => true,
            'invoice_id' => $invoice->getId()?->toRfc4122() ?? $invoice_id,
        ];
    }
}
