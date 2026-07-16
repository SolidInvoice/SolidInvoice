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

namespace SolidInvoice\SaasBundle\Service;

/**
 * Immutable description of the top-of-page subscription banner to render.
 * Carries translation keys + params (not translated text) so the resolver
 * stays free of the translator; RequestListener translates and injects it.
 */
final readonly class TrialBanner
{
    /**
     * @param array<string, string|int> $params translation parameters shared
     *        across the title, message, and CTA (e.g. %days%, %percent%, %code%, %date%)
     */
    public function __construct(
        public string $type,
        public string $icon,
        public string $titleKey,
        public string $messageKey,
        public array $params,
        public string $ctaLabelKey,
        public ?string $code = null,
    ) {
    }
}
