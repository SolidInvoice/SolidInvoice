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
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentMethodOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;

final readonly class PaymentMethodReadTools
{
    public function __construct(
        private PaymentMethodRepository $repository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Fetch a single payment method by its ID.
     *
     * @param string $id Payment method ULID
     */
    #[McpTool(name: 'get_payment_method', title: 'Get payment method', description: 'Fetch a single configured payment method by ID.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(PaymentMethodOutput::class)]
    public function getPaymentMethod(string $id): PaymentMethodOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $method = $this->repository->find(UlidParser::parse($id));

        if (! $method instanceof PaymentMethod) {
            throw new ToolCallException(sprintf('Payment method %s not found.', $id));
        }

        return $this->normalizer->paymentMethod($method);
    }

    /**
     * List payment methods configured for the current company, by name.
     *
     * @param int $page           1-indexed page number
     * @param int $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_payment_methods', title: 'List payment methods', description: 'List configured payment methods for the current company, with pagination.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(PaymentMethodOutput::class, list: true)]
    public function listPaymentMethods(int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return $this->paginator->paginate($this->repository->createQueryBuilder('m')->orderBy('m.name', 'ASC'), $page, $items_per_page);
    }
}
