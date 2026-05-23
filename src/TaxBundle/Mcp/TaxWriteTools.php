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

use Doctrine\ORM\EntityManagerInterface;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\EntityNormalizer;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

final readonly class TaxWriteTools
{
    public function __construct(
        private TaxRepository $repository,
        private EntityManagerInterface $entityManager,
        private EntityNormalizer $normalizer,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Create a new tax rate for the current company.
     *
     * @param string $name     Display name (e.g. "VAT")
     * @param float  $rate     Rate as a percentage (e.g. 15.0 for 15%)
     * @param string $type     One of: Inclusive, Exclusive, Flat Rate
     * @param string $category One of: Standard, ZeroRated, Exempt, OutOfScope, ReverseCharge (default Standard)
     * @param bool   $compound Whether this tax compounds on top of other taxes
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create_tax_rate', description: 'Create a new tax rate (name, rate, type, category, compound flag).')]
    #[McpScopeRequired(McpScope::Write)]
    public function createTaxRate(
        string $name,
        float $rate,
        string $type,
        string $category = 'Standard',
        bool $compound = false,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        if (! \in_array($type, [Tax::TYPE_INCLUSIVE, Tax::TYPE_EXCLUSIVE, Tax::TYPE_FLAT_RATE], true)) {
            throw new ToolCallException(sprintf('Invalid tax type "%s". Use one of: Inclusive, Exclusive, Flat Rate.', $type));
        }

        $categoryEnum = TaxCategory::tryFrom($category);

        if ($categoryEnum === null) {
            throw new ToolCallException(sprintf(
                'Invalid tax category "%s". Use one of: %s.',
                $category,
                implode(', ', array_column(TaxCategory::cases(), 'value')),
            ));
        }

        $tax = new Tax();
        $tax->setName($name);
        $tax->setRate($rate);
        $tax->setType($type);
        $tax->setCategory($categoryEnum);
        $tax->setCompound($compound);

        $this->entityManager->persist($tax);
        $this->entityManager->flush();

        return $this->normalizer->normalize($tax);
    }

    /**
     * Update an existing tax rate.
     *
     * @param string      $tax_id   Tax ULID
     * @param string|null $name     New display name (optional)
     * @param float|null  $rate     New rate as a percentage (optional)
     * @param string|null $type     New type: Inclusive, Exclusive, Flat Rate (optional)
     * @param string|null $category New category: Standard, ZeroRated, Exempt, OutOfScope, ReverseCharge (optional)
     * @param bool|null   $compound Whether this tax compounds on top of other taxes (optional)
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update_tax_rate', description: 'Update an existing tax rate. Pass only the fields you want to change.')]
    #[McpScopeRequired(McpScope::Write)]
    public function updateTaxRate(
        string $tax_id,
        ?string $name = null,
        ?float $rate = null,
        ?string $type = null,
        ?string $category = null,
        ?bool $compound = null,
    ): array {
        $this->scopeGuard->require(McpScope::Write);

        $tax = $this->repository->find(UlidParser::parse($tax_id, 'tax_id'));

        if (! $tax instanceof Tax) {
            throw new ToolCallException(sprintf('Tax rate %s not found.', $tax_id));
        }

        if ($name !== null) {
            $tax->setName($name);
        }

        if ($rate !== null) {
            $tax->setRate($rate);
        }

        if ($type !== null) {
            if (! \in_array($type, [Tax::TYPE_INCLUSIVE, Tax::TYPE_EXCLUSIVE, Tax::TYPE_FLAT_RATE], true)) {
                throw new ToolCallException(sprintf('Invalid tax type "%s". Use one of: Inclusive, Exclusive, Flat Rate.', $type));
            }

            $tax->setType($type);
        }

        if ($category !== null) {
            $categoryEnum = TaxCategory::tryFrom($category);

            if ($categoryEnum === null) {
                throw new ToolCallException(sprintf(
                    'Invalid tax category "%s". Use one of: %s.',
                    $category,
                    implode(', ', array_column(TaxCategory::cases(), 'value')),
                ));
            }

            $tax->setCategory($categoryEnum);
        }

        if ($compound !== null) {
            $tax->setCompound($compound);
        }

        $this->entityManager->flush();

        return $this->normalizer->normalize($tax);
    }
}
