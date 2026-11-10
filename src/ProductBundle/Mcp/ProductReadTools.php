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

namespace SolidInvoice\ProductBundle\Mcp;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ProductOutput;
use SolidInvoice\McpBundle\Mcp\Output\ListOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\Paginator;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class ProductReadTools
{
    public function __construct(
        private ProductRepository $repository,
        private EntityNormalizer $normalizer,
        private Paginator $paginator,
        private McpScopeGuard $scopeGuard,
        private FeatureGate $featureGate,
    ) {
    }

    /**
     * List products in the current company's catalogue, by name.
     *
     * @param string|null $search         Optional text matched against the name and description
     * @param int         $page           1-indexed page number
     * @param int         $items_per_page Results per page (1..100)
     */
    #[McpTool(name: 'list_products', title: 'List products', description: 'List products in the catalogue, optionally filtered by a search term (name or description), with pagination.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ProductOutput::class, list: true)]
    public function listProducts(?string $search = null, int $page = 1, int $items_per_page = 25): ListOutput
    {
        $this->guard();

        return $this->paginator->paginate($this->repository->searchQueryBuilder($search ?? ''), $page, $items_per_page);
    }

    /**
     * Get a single product.
     *
     * @param string $product_id Product ULID
     */
    #[McpTool(name: 'get_product', title: 'Get product', description: 'Get a single product by id.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(ProductOutput::class)]
    public function getProduct(string $product_id): ProductOutput
    {
        $this->guard();

        $product = $this->repository->find(UlidParser::parse($product_id, 'product_id'));

        if (! $product instanceof Product) {
            throw new ToolCallException(sprintf('Product %s not found.', $product_id));
        }

        return $this->normalizer->product($product);
    }

    private function guard(): void
    {
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            throw new ToolCallException('Products are not available on your plan.');
        }

        $this->scopeGuard->require(McpScope::Read);
    }
}
