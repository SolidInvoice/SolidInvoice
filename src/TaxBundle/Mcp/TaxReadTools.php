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
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

final class TaxReadTools
{
    public function __construct(
        private readonly TaxRepository $repository,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List tax rates configured for the current company.
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_tax_rates', description: 'List tax rates configured for the current company.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listTaxRates(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $taxes = $this->repository->createQueryBuilder('t')
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        $results = $this->normalizer->normalizeMany($taxes);

        return ['results' => $results, 'count' => count($results)];
    }
}
