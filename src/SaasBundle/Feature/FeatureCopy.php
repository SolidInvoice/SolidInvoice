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
 * Marketing copy for a single gated feature, surfaced on the full-page gate
 * card and the inline upgrade banner. Strings are stored as English source
 * keys and translated in the template via `|trans`.
 */
final readonly class FeatureCopy
{
    /**
     * @param list<string> $bullets
     */
    public function __construct(
        public string $icon,
        public string $headline,
        public string $description,
        public array $bullets = [],
        public ?string $quote = null,
        public ?string $quoteAuthor = null,
    ) {
    }
}
