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

namespace SolidInvoice\UserBundle\OAuth;

/**
 * A neutral representation of a verified Google identity, produced either by the
 * redirect-based OAuth flow or by the Google One Tap ID token verifier, and
 * consumed by {@see GoogleUserProvisioner}.
 */
final readonly class GoogleIdentity
{
    public function __construct(
        public string $googleId,
        public string $email,
        public bool $emailVerified,
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {
    }
}
