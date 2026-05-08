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

namespace SolidInvoice\CoreBundle\Feature;

/**
 * Renders SaaS upgrade prompts (banner HTML) and resolves the cheapest plan
 * label that exposes a given feature, used by Action classes and menu builders
 * to short-circuit gated UI in SaaS deployments.
 *
 * In self-hosted deployments the implementation is a no-op
 * (`NullUpgradePromptProvider`) — `prompt()` returns an empty string and
 * `menuLabel()` returns `null`. In SaaS deployments
 * `SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer` is wired in.
 *
 * Lives in CoreBundle (not SaasBundle) because Action and menu services are
 * loaded in every deployment and need a stable contract to type-hint against.
 */
interface UpgradePromptProvider
{
    /**
     * Render the locked-feature upgrade banner. Returns an empty string when
     * no upgrade path exists (self-hosted, or every plan exposes the feature).
     */
    public function prompt(string $featureKey): string;

    /**
     * Return the cheapest upgrade plan's display name, or null when no upgrade
     * path is available (self-hosted, or the feature is already enabled).
     */
    public function menuLabel(string $featureKey): ?string;
}
