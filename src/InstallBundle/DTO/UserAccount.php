<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\DTO;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class UserAccount
{
    public function __construct(
        #[NotBlank(message: 'Please select a locale', groups: ['user_account'])]
        public ?string $locale = null,
        #[NotBlank(message: 'Please enter a first name', groups: ['user_account'])]
        public ?string $firstName = null,
        #[NotBlank(message: 'Please enter a last name', groups: ['user_account'])]
        public ?string $lastName = null,
        #[
            NotBlank(message: 'Please enter a email', groups: ['user_account']),
            Email(mode: Email::VALIDATION_MODE_STRICT, groups: ['user_account']),
        ]
        public ?string $emailAddress = null,
        #[
            NotBlank(message: 'You must enter a secure password', groups: ['user_account']),
            Length(min: 6, groups: ['user_account']),
        ]
        public ?string $password = null,
    ) {
    }
}
