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

use RuntimeException;

/**
 * Thrown when a Google One Tap ID token cannot be trusted: bad signature,
 * wrong audience/issuer, expired, or an unverified email address.
 */
final class InvalidIdTokenException extends RuntimeException
{
}
