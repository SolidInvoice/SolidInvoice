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

interface IdTokenVerifierInterface
{
    /**
     * Verify a Google One Tap ID token (a signed JWT) and return its trusted
     * payload.
     *
     * @throws InvalidIdTokenException when the token cannot be trusted
     */
    public function verify(string $idToken): OneTapToken;
}
