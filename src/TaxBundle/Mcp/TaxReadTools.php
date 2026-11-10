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

namespace SolidInvoice\TaxBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

final readonly class TaxReadTools
{
    public function __construct(
        private TaxRepository $repository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single tax rate by its ID.
     *
     * @param string $id Tax ULID
     */
    #[McpTool(name: 'get_tax_rate', title: 'Get tax rate', description: 'Fetch a single tax rate by ID.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(TaxOutput::class)]
    public function getTaxRate(string $id): TaxOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $tax = $this->repository->find(UlidParser::parse($id));

        if (! $tax instanceof Tax) {
            throw new ToolCallException(sprintf('Tax rate %s not found.', $id));
        }

        return $this->normalizer->tax($tax);
    }

    /**
     * List tax rates configured for the current company, by name.
     *
     * @param int $page           1-indexed page number
     * @param int $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_tax_rates', title: 'List tax rates', description: 'List tax rates configured for the current company, with pagination.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(TaxOutput::class, list: true)]
    public function listTaxRates(int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return $this->paginator->paginate($this->repository->createQueryBuilder('t')->orderBy('t.name', 'ASC'), $page, $items_per_page);
    }
}
