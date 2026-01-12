<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\DTO;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

#[UniqueEntity(['email'], entityClass: User::class)]
final class Registration
{
    #[
        NotBlank,
        Email(['mode' => Email::VALIDATION_MODE_STRICT]),
    ]
    public ?string $email = null;

    #[
        NotBlank(message: 'Please enter a password'),
        Length(
            min: 8,
            max: 4096,
            // max length allowed by Symfony for security reasons
            minMessage: 'Your password should be at least {{ limit }} characters',
        ),
        PasswordStrength(minScore: PasswordStrength::STRENGTH_WEAK)]
    public ?string $plainPassword = null;

    #[IsTrue(message: 'You must accept the terms and conditions to register')]
    public ?bool $acceptTerms = null;
}
