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
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\LineItemBuilder;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final class RecurringInvoiceWriteTools
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly LineItemBuilder $lineItemBuilder,
        private readonly TotalCalculator $totalCalculator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        #[Autowire(service: 'state_machine.recurring_invoice')]
        private readonly WorkflowInterface $recurringWorkflow,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Create a new recurring invoice for a client. The recurring schedule is
     * expressed via the `schedule` argument (type + optional end condition).
     * Totals are computed server-side; the record starts in the "new" state —
     * use `apply_recurring_transition` to activate it.
     *
     * @param string                          $client_id   Client ULID
     * @param list<array<string, mixed>>      $lines       Line items: [{description, price, qty, tax_id?}, ...]
     * @param string                          $date_start  ISO-8601 date the first invoice should be generated
     * @param array<string, mixed>            $schedule    {"type": "daily"|"weekly"|"monthly"|"yearly",
     *                                                     "end_type": "never"|"on"|"after" (default "never"),
     *                                                     "end_date": ISO-8601 (required if end_type="on"),
     *                                                     "end_occurrence": int (required if end_type="after"),
     *                                                     "days": list<int>}
     * @param string|null                     $date_end       Hard stop date for generated invoices (optional)
     * @param string|null                     $discount_type  "percentage" or "money" (optional)
     * @param int|float|null                  $discount_value Numeric discount value; required if discount_type is set
     * @param string|null                     $terms          Optional terms text
     * @param string|null                     $notes          Optional notes text
     * @param list<string>                    $contact_ids    Client contact ULIDs to attach (optional)
     * @param bool                            $activate       If true, also transition from "new" -> "active" after create
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_recurring_invoice', description: 'Create a new recurring invoice for a client with a scheduled cadence (daily/weekly/monthly/yearly).')]
    #[McpScopeRequired(McpScope::Write)]
    public function createRecurringInvoice(
        string $client_id,
        array $lines,
        string $date_start,
        array $schedule,
        ?string $date_end = null,
        ?string $discount_type = null,
        int|float|null $discount_value = null,
        ?string $terms = null,
        ?string $notes = null,
        array $contact_ids = [],
        bool $activate = false,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $invoice = new RecurringInvoice();
        $invoice->setClient($client);

        try {
            $invoice->setDateStart(new DateTimeImmutable($date_start));
        } catch (\Exception) {
            throw new ToolCallException(sprintf('Invalid date_start "%s". Use ISO-8601.', $date_start));
        }

        if ($date_end !== null) {
            try {
                $invoice->setDateEnd(new DateTimeImmutable($date_end));
            } catch (\Exception) {
                throw new ToolCallException(sprintf('Invalid date_end "%s". Use ISO-8601.', $date_end));
            }
        }

        foreach ($this->lineItemBuilder->buildRecurringInvoiceLines($lines) as $line) {
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

        $invoice->setRecurringOptions($this->buildRecurringOptions($invoice, $schedule));

        $this->totalCalculator->calculateTotals($invoice);

        // Seed status to satisfy the NOT NULL column, then run the workflow transitions.
        $invoice->setStatus(RecurringInvoiceStatus::New);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        if ($this->recurringWorkflow->can($invoice, InvoiceGraph::TRANSITION_NEW)) {
            $this->recurringWorkflow->apply($invoice, InvoiceGraph::TRANSITION_NEW);
            $this->entityManager->flush();
        }

        if ($activate && $this->recurringWorkflow->can($invoice, InvoiceGraph::TRANSITION_ACTIVATE)) {
            $this->recurringWorkflow->apply($invoice, InvoiceGraph::TRANSITION_ACTIVATE);
            $this->entityManager->flush();
        }

        return $this->normalizer->normalize($invoice);
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function buildRecurringOptions(RecurringInvoice $invoice, array $schedule): RecurringOptions
    {
        $options = new RecurringOptions();
        $options->setRecurringInvoice($invoice);

        $type = $schedule['type'] ?? null;

        if (! \is_string($type)) {
            throw new ToolCallException('schedule.type is required (daily|weekly|monthly|yearly).');
        }

        $recurringType = ScheduleRecurringType::tryFrom($type);

        if ($recurringType === null) {
            throw new ToolCallException(sprintf('Invalid schedule.type "%s". Expected one of: %s.', $type, implode(', ', array_map(static fn (ScheduleRecurringType $t): string => $t->value, ScheduleRecurringType::cases()))));
        }

        $options->setType($recurringType);

        $days = $schedule['days'] ?? [];

        if (! \is_array($days)) {
            throw new ToolCallException('schedule.days must be an array of integers.');
        }

        $intDays = [];

        foreach ($days as $day) {
            if (! is_numeric($day)) {
                throw new ToolCallException('schedule.days entries must be integers.');
            }

            $intDays[] = (int) $day;
        }

        $options->setDays($intDays);

        $endType = $schedule['end_type'] ?? 'never';

        if (! \is_string($endType)) {
            throw new ToolCallException('schedule.end_type must be a string.');
        }

        $endTypeEnum = ScheduleEndType::tryFrom($endType);

        if ($endTypeEnum === null) {
            throw new ToolCallException(sprintf('Invalid schedule.end_type "%s". Expected: never|on|after.', $endType));
        }

        $options->setEndType($endTypeEnum);

        if ($endTypeEnum === ScheduleEndType::ON) {
            $endDate = $schedule['end_date'] ?? null;

            if (! \is_string($endDate) || $endDate === '') {
                throw new ToolCallException('schedule.end_date is required when end_type = "on".');
            }

            try {
                $options->setEndDate(new DateTimeImmutable($endDate));
            } catch (\Exception) {
                throw new ToolCallException(sprintf('Invalid schedule.end_date "%s". Use ISO-8601.', $endDate));
            }
        }

        if ($endTypeEnum === ScheduleEndType::AFTER) {
            $endOccurrence = $schedule['end_occurrence'] ?? null;

            if (! is_numeric($endOccurrence) || (int) $endOccurrence < 1) {
                throw new ToolCallException('schedule.end_occurrence must be a positive integer when end_type = "after".');
            }

            $options->setEndOccurrence((int) $endOccurrence);
        }

        return $options;
    }
}
