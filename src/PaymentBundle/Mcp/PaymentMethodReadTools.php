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

namespace SolidInvoice\PaymentBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;

final class PaymentMethodReadTools
{
    public function __construct(
        private readonly PaymentMethodRepository $repository,
        private readonly EntityNormalizer $normalizer,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * List payment methods configured for the current company.
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_payment_methods', description: 'List configured payment methods for the current company.')]
    #[McpScopeRequired(McpScope::Read)]
    public function listPaymentMethods(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $methods = $this->repository->createQueryBuilder('m')
            ->orderBy('m.name', 'ASC')
            ->getQuery()
            ->getResult();

        $results = $this->normalizer->normalizeMany($methods);

        return ['results' => $results, 'count' => count($results)];
    }
}
