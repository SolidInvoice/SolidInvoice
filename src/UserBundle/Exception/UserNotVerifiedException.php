<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Exception;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Throwable;

final class UserNotVerifiedException extends CustomUserMessageAccountStatusException
{
    public function __construct(string $message = 'Email address not verified', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, [], $code, $previous);
    }

    public function getMessageKey(): string
    {
        return 'Email address not verified';
    }
}
