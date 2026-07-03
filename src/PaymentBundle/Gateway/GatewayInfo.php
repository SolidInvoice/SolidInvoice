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

namespace SolidInvoice\PaymentBundle\Gateway;

/**
 * Immutable presentation metadata for a single payment gateway.
 *
 * This is the single source of truth for how a gateway is described to the user
 * (display name, tagline, icon, setup instructions, …). String properties that
 * are shown to the user hold translation keys, which are resolved with `|trans`
 * in the templates. The one exception is {@see self::$setupUrl}, which is a real
 * URL and is never translated.
 */
final readonly class GatewayInfo
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $tagline,
        public string $icon,
        public GatewayCategory $category,
        public bool $recommended = false,
        public bool $legacy = false,
        public ?string $overview = null,
        public ?string $comparison = null,
        public ?string $setupUrl = null,
        public ?string $setupUrlLabel = null,
    ) {
    }

    /**
     * Whether there is any instructional content worth rendering in the
     * "About this gateway" panel.
     */
    public function hasGuidance(): bool
    {
        return $this->overview !== null || $this->setupUrl !== null || $this->comparison !== null;
    }

    /**
     * A non-recommended online gateway that has a simpler, recommended sibling —
     * used to render an "Advanced" hint on the marketplace card.
     */
    public function isAdvanced(): bool
    {
        return ! $this->recommended && ! $this->legacy && $this->category === GatewayCategory::Online;
    }
}
