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

namespace SolidInvoice\QuoteBundle\Mcp;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\LineItemBuilder;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final class QuoteWriteTools
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly ClientRepository $clientRepository,
        private readonly QuoteCloner $cloner,
        private readonly InvoiceManager $invoiceManager,
        private readonly LineItemBuilder $lineItemBuilder,
        private readonly TotalCalculator $totalCalculator,
        private readonly BillingIdGenerator $billingIdGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        #[Autowire(service: 'state_machine.quote')]
        private readonly WorkflowInterface $quoteWorkflow,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Create a new quote for an existing client.
     *
     * Totals are calculated server-side from the line items. The quote starts
     * as a draft — use `apply_quote_transition` to send/accept/etc.
     *
     * @param string                          $client_id      Client ULID (must belong to the active company)
     * @param list<array<string, mixed>>      $lines          Line items: [{description, price, qty, tax_id?}, ...]
     * @param string|null                     $due            ISO-8601 due date (optional)
     * @param string|null                     $discount_type  "percentage" or "money" (optional)
     * @param int|float|null                  $discount_value Numeric discount value; required if discount_type is set
     * @param string|null                     $terms          Optional terms text
     * @param string|null                     $notes          Optional notes text
     * @param list<string>                    $contact_ids    Client contact ULIDs to attach (optional)
     * @param string|null                     $quote_id       Explicit quote number (generated if omitted)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_quote', description: 'Create a new quote for a client, with line items, optional discount, due date, and contacts.')]
    #[McpScopeRequired(McpScope::Write)]
    public function createQuote(
        string $client_id,
        array $lines,
        ?string $due = null,
        ?string $discount_type = null,
        int|float|null $discount_value = null,
        ?string $terms = null,
        ?string $notes = null,
        array $contact_ids = [],
        ?string $quote_id = null,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $client_id));
        }

        $quote = new Quote();
        $quote->setClient($client);

        if ($due !== null) {
            try {
                $quote->setDue(new DateTimeImmutable($due));
            } catch (\Exception) {
                throw new ToolCallException(sprintf('Invalid due date "%s". Use ISO-8601 format (YYYY-MM-DD).', $due));
            }
        }

        foreach ($this->lineItemBuilder->buildQuoteLines($lines) as $line) {
            $quote->addLine($line);
        }

        $quote->setDiscount($this->lineItemBuilder->buildDiscount($discount_type, $discount_value));

        if ($terms !== null) {
            $quote->setTerms($terms);
        }

        if ($notes !== null) {
            $quote->setNotes($notes);
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

            $quote->addUser($contact);
        }

        $quote->setQuoteId(
            $quote_id !== null && $quote_id !== ''
                ? $quote_id
                : $this->billingIdGenerator->generate($quote, ['field' => 'quoteId']),
        );

        $this->totalCalculator->calculateTotals($quote);

        // Seed the status so the NOT NULL column is satisfied before the workflow
        // apply() moves it to the actual initial "new" state.
        $quote->setStatus(QuoteStatus::New);
        $this->entityManager->persist($quote);
        $this->entityManager->flush();

        if ($this->quoteWorkflow->can($quote, QuoteGraph::TRANSITION_NEW)) {
            $this->quoteWorkflow->apply($quote, QuoteGraph::TRANSITION_NEW);
            $this->entityManager->flush();
        }

        return $this->normalizer->normalize($quote);
    }

    /**
     * Apply a workflow transition to a quote.
     *
     * @param string $quote_id   Quote ULID
     * @param string $transition One of: send, accept, decline, cancel, reopen, archive
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'apply_quote_transition', description: 'Apply a workflow transition (send, accept, decline, cancel, reopen, archive) to a quote.')]
    #[McpScopeRequired(McpScope::Write)]
    public function applyQuoteTransition(string $quote_id, string $transition): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $quote = $this->quoteRepository->find(UlidParser::parse($quote_id, 'quote_id'));

        if (! $quote instanceof Quote) {
            throw new ToolCallException(sprintf('Quote %s not found.', $quote_id));
        }

        if (! $this->quoteWorkflow->can($quote, $transition)) {
            throw new ToolCallException(sprintf(
                'Transition "%s" is not enabled for quote in status "%s".',
                $transition,
                $quote->getStatus()?->value ?? 'unknown',
            ));
        }

        $this->quoteWorkflow->apply($quote, $transition);
        $this->entityManager->flush();

        return $this->normalizer->normalize($quote);
    }

    /**
     * Clone an existing quote into a new draft.
     *
     * @param string $quote_id Quote ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'clone_quote', description: 'Clone an existing quote into a new draft with the same line items.')]
    #[McpScopeRequired(McpScope::Write)]
    public function cloneQuote(string $quote_id): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $quote = $this->quoteRepository->find(UlidParser::parse($quote_id, 'quote_id'));

        if (! $quote instanceof Quote) {
            throw new ToolCallException(sprintf('Quote %s not found.', $quote_id));
        }

        $cloned = $this->cloner->clone($quote);

        // QuoteCloner::clone() only builds the entity and applies the workflow
        // transition; it does not persist or flush. Persist here so the MCP
        // caller gets a saved entity (and a real id in the response).
        $this->entityManager->persist($cloned);
        $this->entityManager->flush();

        return $this->normalizer->normalize($cloned);
    }

    /**
     * Convert an accepted quote into a new invoice.
     *
     * @param string $quote_id Quote ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'convert_quote_to_invoice', description: 'Convert a quote into a new invoice, copying client, line items, and totals.')]
    #[McpScopeRequired(McpScope::Write)]
    public function convertQuoteToInvoice(string $quote_id): array
    {
        $this->scopeGuard->require(McpScope::Write);

        $quote = $this->quoteRepository->find(UlidParser::parse($quote_id, 'quote_id'));

        if (! $quote instanceof Quote) {
            throw new ToolCallException(sprintf('Quote %s not found.', $quote_id));
        }

        if ($quote->getInvoice() !== null) {
            throw new ToolCallException('This quote has already been converted to an invoice.');
        }

        if ($quote->getStatus() !== QuoteStatus::Accepted) {
            throw new ToolCallException(sprintf(
                'Only accepted quotes can be converted to an invoice. Current status: "%s".',
                $quote->getStatus()?->value ?? 'unknown',
            ));
        }

        $invoice = $this->invoiceManager->createFromQuote($quote);
        $invoice = $this->invoiceManager->create($invoice);

        return $this->normalizer->normalize($invoice);
    }
}
