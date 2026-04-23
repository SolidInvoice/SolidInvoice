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

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Cloner\QuoteCloner;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final class QuoteWriteTools
{
    public function __construct(
        private readonly QuoteRepository $quoteRepository,
        private readonly QuoteCloner $cloner,
        private readonly InvoiceManager $invoiceManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityNormalizer $normalizer,
        #[Autowire(service: 'state_machine.quote')]
        private readonly WorkflowInterface $quoteWorkflow,
        private readonly McpScopeGuard $scopeGuard,
    ) {
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

        $invoice = $this->invoiceManager->createFromQuote($quote);
        $invoice = $this->invoiceManager->create($invoice);

        return $this->normalizer->normalize($invoice);
    }
}
