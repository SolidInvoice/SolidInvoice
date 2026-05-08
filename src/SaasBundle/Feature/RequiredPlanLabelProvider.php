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

namespace SolidInvoice\SaasBundle\Feature;

/**
 * Resolves a short, human-readable label naming the cheapest plan that exposes
 * a given feature — used to render upgrade prompts ("Subscription Required —
 * {plan}"). Returns `null` when no upgrade path is available (self-hosted, or
 * the feature is already enabled, or no plan can be resolved).
 *
 * `UpgradePromptRenderer` is the canonical implementation. The interface exists
 * so dependents (e.g. `FeatureRestrictedExtension`) can be unit-tested without
 * having to instantiate the full Twig + Plan repository graph.
 */
interface RequiredPlanLabelProvider
{
    public function menuLabel(string $featureKey): ?string;
}
