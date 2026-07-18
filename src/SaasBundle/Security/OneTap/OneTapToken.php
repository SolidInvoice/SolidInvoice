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

namespace SolidInvoice\SaasBundle\Security\OneTap;

use SolidInvoice\UserBundle\OAuth\GoogleIdentity;

/**
 * The verified payload of a Google One Tap ID token: the resolved identity plus
 * the (optional) nonce claim used for replay protection.
 */
final readonly class OneTapToken
{
    public function __construct(
        public GoogleIdentity $identity,
        public ?string $nonce,
    ) {
    }
}
