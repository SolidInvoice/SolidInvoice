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

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\NumberSyntax;
use Doctrine\ORM\EntityManagerInterface;
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
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ProductWriteTools
{
    private const int MAX_DIGITS = 64;

    public function __construct(
        private ProductRepository $repository,
        private TaxRepository $taxRepository,
        private EntityManagerInterface $entityManager,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
        private ValidatorInterface $validator,
        private FeatureGate $featureGate,
    ) {
    }

    /**
     * @param string      $name        Product name
     * @param string      $price       Unit price as a decimal string in major units, no currency (e.g. "49.99")
     * @param string|null $description Optional description
     * @param string      $quantity    Default quantity as a decimal string (default "1")
     * @param string|null $tax_id      Optional tax rate ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_product', description: 'Create a catalogue product (name, price, description, default quantity, tax rate). Price and quantity are decimal strings.')]
    #[McpScopeRequired(McpScope::Write)]
    public function createProduct(
        string $name,
        string $price,
        ?string $description = null,
        string $quantity = '1',
        ?string $tax_id = null,
    ): array {
        $this->guard();

        $product = new Product();
        $product->setName($name);
        $product->setDescription($description);
        $product->setPrice($this->number($price, 'price'));
        $product->setQuantity($this->number($quantity, 'quantity'));
        $product->setTax($tax_id === null ? null : $this->tax($tax_id));

        $this->validate($product);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->normalizer->normalize($product);
    }

    /**
     * Update a product. Only the fields passed are changed.
     *
     * @param string      $product_id  Product ULID
     * @param string|null $name        New name
     * @param string|null $price       New unit price, decimal string in major units
     * @param string|null $description New description
     * @param string|null $quantity    New default quantity, decimal string
     * @param string|null $tax_id      New tax rate ULID
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update_product', description: 'Update a product. Pass only the fields you want to change.')]
    #[McpScopeRequired(McpScope::Write)]
    public function updateProduct(
        string $product_id,
        ?string $name = null,
        ?string $price = null,
        ?string $description = null,
        ?string $quantity = null,
        ?string $tax_id = null,
    ): array {
        $this->guard();

        $product = $this->find($product_id);

        if ($name !== null) {
            $product->setName($name);
        }

        if ($price !== null) {
            $product->setPrice($this->number($price, 'price'));
        }

        if ($description !== null) {
            $product->setDescription($description);
        }

        if ($quantity !== null) {
            $product->setQuantity($this->number($quantity, 'quantity'));
        }

        if ($tax_id !== null) {
            $product->setTax($this->tax($tax_id));
        }

        try {
            $this->validate($product);
        } catch (ToolCallException $e) {
            // Leave no half-applied edits on the managed entity.
            $this->entityManager->refresh($product);

            throw $e;
        }

        $this->entityManager->flush();

        return $this->normalizer->normalize($product);
    }

    /**
     * Delete a product. Lines already created from it are unaffected.
     *
     * @param string $product_id Product ULID
     *
     * @return array{deleted: bool, id: string}
     */
    #[McpTool(name: 'delete_product', description: 'Delete a product from the catalogue.')]
    #[McpScopeRequired(McpScope::Write)]
    public function deleteProduct(string $product_id): array
    {
        $this->guard();

        $product = $this->find($product_id);
        $id = $product->getId()?->toRfc4122() ?? $product_id;

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return ['deleted' => true, 'id' => $id];
    }

    private function guard(): void
    {
        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            throw new ToolCallException('Products are not available on your plan.');
        }

        $this->scopeGuard->require(McpScope::Write);
    }

    private function find(string $productId): Product
    {
        $product = $this->repository->find(UlidParser::parse($productId, 'product_id'));

        if (! $product instanceof Product) {
            throw new ToolCallException(sprintf('Product %s not found.', $productId));
        }

        return $product;
    }

    private function tax(string $taxId): Tax
    {
        $tax = $this->taxRepository->find(UlidParser::parse($taxId, 'tax_id'));

        if (! $tax instanceof Tax) {
            throw new ToolCallException(sprintf('Tax rate %s not found.', $taxId));
        }

        return $tax;
    }

    /**
     * Agent input is untrusted: parse() counts digits as written and expanded, so a huge digit
     * string or an exponent such as "1e999999999" is refused before it is allocated.
     */
    private function number(string $value, string $field): BigNumber
    {
        try {
            return BigNumber::parse($value, NumberSyntax::SCIENTIFIC, self::MAX_DIGITS);
        } catch (MathException) {
            throw new ToolCallException(sprintf('Invalid %s: "%s" is not a valid number of at most %d digits.', $field, mb_strimwidth($value, 0, 32, '...'), self::MAX_DIGITS));
        }
    }

    private function validate(Product $product): void
    {
        $violations = $this->validator->validate($product);

        if (\count($violations) === 0) {
            return;
        }

        $messages = [];

        foreach ($violations as $violation) {
            $messages[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        throw new ToolCallException('Invalid product. ' . implode('; ', $messages));
    }
}
