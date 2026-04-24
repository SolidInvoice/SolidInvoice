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

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Cloner\InvoiceCloner;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\LineItemBuilder;
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
        private readonly ClientRepository $clientRepository,
        private readonly InvoiceCloner $cloner,
        private readonly InvoiceManager $invoiceManager,
        private readonly LineItemBuilder $lineItemBuilder,
        private readonly TotalCalculator $totalCalculator,
        private readonly BillingIdGenerator $billingIdGenerator,
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
     * Create a new invoice for an existing client.
     *
     * Totals (subtotal / tax / discount / total) are calculated server-side
     * from the line items. The invoice starts as a draft — apply
     * `apply_invoice_transition` to move it through the workflow.
     *
     * @param string                          $client_id      Client ULID (must belong to the active company)
     * @param list<array<string, mixed>>      $lines          Line items: [{description, price, qty, tax_id?}, ...].
     *                                                        Price is in the minor unit (e.g. cents).
     * @param string|null                     $invoice_date   ISO-8601 date (defaults to today)
     * @param string|null                     $due            ISO-8601 due date (optional)
     * @param string|null                     $discount_type  "percentage" or "money" (optional)
     * @param int|float|null                  $discount_value Numeric discount value; required if discount_type is set
     * @param string|null                     $terms          Optional terms text
     * @param string|null                     $notes          Optional notes text
     * @param list<string>                    $contact_ids    Client contact ULIDs to attach to the invoice (optional)
     * @param string|null                     $invoice_id     Explicit invoice number (generated if omitted)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_invoice', description: 'Create a new invoice for a client, with line items, optional discount, due date, and contacts.')]
    #[McpScopeRequired(McpScope::Write)]
    public function createInvoice(
        string $client_id,
        array $lines,
        ?string $invoice_date = null,
        ?string $due = null,
        ?string $discount_type = null,
        int|float|null $discount_value = null,
        ?string $terms = null,
        ?string $notes = null,
        array $contact_ids = [],
        ?string $invoice_id = null,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $invoice = new Invoice();
        $invoice->setClient($client);
        $invoice->setInvoiceDate($this->parseDate($invoice_date, new DateTimeImmutable()));

        if ($due !== null) {
            $invoice->setDue($this->parseDate($due, new DateTimeImmutable()));
        }

        foreach ($this->lineItemBuilder->buildInvoiceLines($lines) as $line) {
            $invoice->addLine($line);
        }

        $invoice->setDiscount($this->lineItemBuilder->buildDiscount($discount_type, $discount_value));

        if ($terms !== null) {
            $invoice->setTerms($terms);
        }

        if ($notes !== null) {
            $invoice->setNotes($notes);
        }

        foreach ($contact_ids as $index => $contactId) {
            if (! \is_string($contactId)) {
                throw new ToolCallException(sprintf('contact_ids[%d] must be a string.', $index));
            }

            $contact = $this->entityManager
                ->getRepository(Contact::class)
                ->find(UlidParser::parse($contactId, sprintf('contact_ids[%d]', $index)));

            if (! $contact instanceof Contact || $contact->getClient()?->getId()?->equals($client->getId()) !== true) {
                throw new ToolCallException(sprintf('Contact %s does not belong to this client.', $contactId));
            }

            $invoice->addUser($contact);
        }

        $invoice->setInvoiceId(
            $invoice_id !== null && $invoice_id !== ''
                ? $invoice_id
                : $this->billingIdGenerator->generate($invoice, ['field' => 'invoiceId']),
        );

        $this->totalCalculator->calculateTotals($invoice);

        $invoice = $this->invoiceManager->create($invoice);

        return $this->normalizer->normalize($invoice);
    }

    private function parseDate(?string $value, DateTimeImmutable $default): DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return $default;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ToolCallException(sprintf('Invalid date "%s". Use ISO-8601 format (YYYY-MM-DD).', $value));
        }
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

        // InvoiceCloner::clone() only persists+flushes Invoice clones
        // (via invoiceManager->create()); RecurringInvoice clones come back
        // transient. Persist here so the MCP caller gets a saved entity and
        // the normalised payload exposes a real id rather than null.
        if ($cloned instanceof RecurringInvoice) {
            $this->entityManager->persist($cloned);
            $this->entityManager->flush();
        }

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
            // Don't surface transport-level detail (SMTP host, auth failures,
            // relay internals) to the AI agent — log it and keep the user
            // message generic.
            $this->logger->error('Failed to send manual MCP reminder', [
                'invoice_id' => $invoice->getInvoiceId(),
                'exception' => $exception,
            ]);

            throw new ToolCallException('Failed to send reminder. See server logs for details.');
        }

        return [
            'sent' => true,
            'invoice_id' => $invoice->getId()?->toRfc4122() ?? $invoice_id,
        ];
    }
}
