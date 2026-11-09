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

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\QuoteOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

final readonly class QuoteReadTools
{
    public function __construct(
        private QuoteRepository $quoteRepository,
        private ClientRepository $clientRepository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single quote by its ID.
     *
     * @param string $id Quote ULID
     */
    #[McpTool(name: 'get_quote', title: 'Get quote', description: 'Fetch a single quote by ID, with its lines, taxes and totals.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(QuoteOutput::class)]
    public function getQuote(string $id): QuoteOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $quote = $this->quoteRepository->find(UlidParser::parse($id));

        if (! $quote instanceof Quote) {
            throw new ToolCallException(sprintf('Quote %s not found.', $id));
        }

        return $this->normalizer->quote($quote);
    }

    /**
     * List quotes, newest first, optionally filtered by status and/or client.
     *
     * @param string|null $status         One of: new, draft, pending, accepted, cancelled, declined, archived, expired (optional)
     * @param string|null $client_id      Client ULID (optional)
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_quotes', title: 'List quotes', description: 'List quotes with pagination, optionally filtered by status and/or client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(QuoteOutput::class, list: true)]
    public function listQuotes(?string $status = null, ?string $client_id = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $qb = $this->quoteRepository->createQueryBuilder('q')->orderBy('q.created', 'DESC');

        if ($status !== null) {
            $statusEnum = QuoteStatus::tryFrom($status) ?? throw new ToolCallException(sprintf(
                'Invalid status "%s". Valid values: %s.',
                $status,
                implode(', ', array_column(QuoteStatus::cases(), 'value')),
            ));

            $qb->andWhere('q.status = :status')->setParameter('status', $statusEnum);
        }

        if ($client_id !== null) {
            $client = $this->clientRepository->find(UlidParser::parse($client_id, 'client_id'));

            if (! $client instanceof Client) {
                throw new ToolCallException(sprintf('Client %s not found.', $client_id));
            }

            $qb->andWhere('q.client = :client')->setParameter('client', $client->getId(), UlidType::NAME);
        }

        return $this->paginator->paginate($qb, $page, $items_per_page);
    }
}
