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

namespace SolidInvoice\InstallBundle\DTO;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class UserAccount
{
    public function __construct(
        #[NotBlank(message: 'install.user_account.locale.not_blank', groups: ['user_account'])]
        public ?string $locale = null,
        #[NotBlank(message: 'install.user_account.first_name.not_blank', groups: ['user_account'])]
        public ?string $firstName = null,
        #[NotBlank(message: 'install.user_account.last_name.not_blank', groups: ['user_account'])]
        public ?string $lastName = null,
        #[
            NotBlank(message: 'install.user_account.email.not_blank', groups: ['user_account']),
            Email(mode: Email::VALIDATION_MODE_STRICT, groups: ['user_account']),
        ]
        public ?string $emailAddress = null,
        #[
            NotBlank(message: 'install.user_account.password.not_blank', groups: ['user_account']),
            Length(min: 6, groups: ['user_account']),
        ]
        public ?string $password = null,
    ) {
    }
}
