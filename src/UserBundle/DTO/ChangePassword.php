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

namespace SolidInvoice\UserBundle\DTO;

use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

final class ChangePassword
{
    #[NotBlank]
    #[UserPassword]
    public ?string $currentPassword = null;

    #[NotBlank(message: 'Please enter a password')]
    #[Length(
        min: 8,
        minMessage: 'Your password must be at least {{ limit }} characters long',
        max: 4096
    )]
    #[PasswordStrength(
        minScore: PasswordStrength::STRENGTH_MEDIUM,
        message: 'Your password is too weak. Please use a stronger password with a mix of letters, numbers, and symbols.'
    )]
    #[NotCompromisedPassword(
        message: 'This password has been leaked in a data breach, please use a different password.'
    )]
    public ?string $plainPassword = null;
}
