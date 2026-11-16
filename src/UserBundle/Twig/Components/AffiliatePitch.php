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

namespace SolidInvoice\UserBundle\Twig\Components;

use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidInvoice\UserBundle\Affiliate\AffiliateSource;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * What the affiliate programme pays, how it works, and the button to join. Shared by the
 * dashboard prompt and the "Refer & earn" page so the two never drift apart.
 */
#[AsTwigComponent]
final class AffiliatePitch
{
    /**
     * An {@see AffiliateSource} value, so the join click is counted against where it happened.
     */
    public string $source = AffiliateSource::Page->value;

    /**
     * A Stimulus `data-action` for the join button, for a caller that has to react to the click.
     */
    public string $ctaAction = '';

    public function __construct(
        public readonly AffiliateProgram $program,
    ) {
    }
}
