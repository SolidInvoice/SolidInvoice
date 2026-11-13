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

namespace SolidInvoice\DashboardBundle\Checklist\Items;

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\ProductBundle\Repository\ProductRepository;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class AddProductItem implements ChecklistItemInterface
{
    public function __construct(
        private ProductRepository $productRepository,
        private FeatureGate $featureGate,
    ) {
    }

    public function getName(): string
    {
        return 'dashboard.checklist.add_product.name';
    }

    public function getDescription(): string
    {
        return 'dashboard.checklist.add_product.description';
    }

    public function getIcon(): string
    {
        return 'tabler:package';
    }

    public function getRoute(): string
    {
        return '_products_add';
    }

    public function getPriority(): int
    {
        return -450;
    }

    public function active(): bool
    {
        return $this->featureGate->isEnabled(Feature::Products->value);
    }

    public function isComplete(): bool
    {
        // Company filter ensures we only count products for the current company
        return $this->productRepository->count([]) > 0;
    }
}
