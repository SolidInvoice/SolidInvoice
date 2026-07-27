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

use EmailChecker\Constraints as EmailChecker;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(['email'], entityClass: User::class)]
final class Registration
{
    #[
        Assert\NotBlank,
        Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT),
        EmailChecker\NotThrowawayEmail(message: 'Disposable or temporary email addresses are not allowed. Please use a permanent email address.'),
    ]
    public ?string $email = null;

    #[
        Assert\NotBlank(message: 'user.password.not_blank'),
        Assert\Length(
            min: 8,
            max: 4096,
            // max length allowed by Symfony for security reasons
            minMessage: 'user.password.min_length',
        ),
        Assert\PasswordStrength(minScore: Assert\PasswordStrength::STRENGTH_WEAK)]
    public ?string $plainPassword = null;

    #[Assert\IsTrue(message: 'user.register.accept_terms')]
    public ?bool $acceptTerms = null;
}
