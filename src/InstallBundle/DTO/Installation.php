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

use Symfony\Component\Validator\Constraints\Valid;

final class Installation
{
    public function __construct(
        #[Valid(groups: ['database_config', 'database_config_mysql', 'database_config_mariadb', 'database_config_pgsql'])]
        public DatabaseConfig $databaseConfig = new DatabaseConfig(),
        #[Valid(groups: ['user_account'])]
        public UserAccount $userAccount = new UserAccount(),
        public ?string $applicationUrl = null,
        public string $currentStep = 'start',
        public ?string $token = '',
    ) {
    }
}
