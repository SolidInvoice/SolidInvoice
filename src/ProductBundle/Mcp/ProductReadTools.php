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
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class ProductReadTools
{
    // ponytail: first 100 matches only, add paging if catalogues outgrow it
    private const int LIMIT = 100;

    public function __construct(
        private ProductRepository $repository,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
        private FeatureGate $featureGate,
    ) {
    }

    /**
     * List products in the current company's catalogue.
     *
     * @param string|null $search Optional text matched against the name and description
     *
     * @return array{results: list<array<string, mixed>>, count: int}
     */
    #[McpTool(name: 'list_products', description: 'List products in the catalogue, optionally filtered by a search term (name or description).')]
    #[McpScopeRequired(McpScope::Read)]
    public function listProducts(?string $search = null): array
    {
        $this->guard();

        $results = $this->normalizer->normalizeMany($this->repository->search($search ?? '', self::LIMIT));

        return ['results' => $results, 'count' => count($results)];
    }

    /**
     * Get a single product.
     *
     * @param string $product_id Product ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get_product', description: 'Get a single product by id.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getProduct(string $product_id): array
    {
        $this->guard();

        $product = $this->repository->find(UlidParser::parse($product_id, 'product_id'));

        if (! $product instanceof Product) {
            throw new ToolCallException(sprintf('Product %s not found.', $product_id));
        }

        return $this->normalizer->normalize($product);
    }

    private function guard(): void
    {
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            throw new ToolCallException('Products are not available on your plan.');
        }

        $this->scopeGuard->require(McpScope::Read);
    }
}
