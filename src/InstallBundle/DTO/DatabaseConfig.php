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

use Doctrine\DBAL\DriverManager;
use PDO;
use SolidInvoice\CoreBundle\SolidInvoiceCoreBundle;
use SolidInvoice\InstallBundle\Doctrine\Drivers;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Throwable;

#[Callback(
    callback: 'validate',
    groups: ['database_config'],
)]
final class DatabaseConfig
{
    public function __construct(
        #[NotBlank(message: 'Please select a database driver.', groups: ['database_config'])]
        public ?string $driver = null,
        #[NotBlank(groups: ['database_config_mysql', 'database_config_mariadb', 'database_config_pgsql'])]
        public ?string $host = null,
        #[Type(type: 'integer', groups: ['database_config_mysql', 'database_config_mariadb', 'database_config_pgsql'])]
        public ?int $port = null,
        public ?string $user = null,
        public ?string $password = null,
        public ?string $version = null,
        #[NotBlank(groups: ['database_config_mysql', 'database_config_mariadb', 'database_config_pgsql'])]
        public ?string $name = SolidInvoiceCoreBundle::APP_NAME,
    ) {
    }

    public static function validate(self $data, ExecutionContextInterface $executionContext): void
    {
        if (null !== $data->driver && 'sqlite' !== $data->driver && null !== $data->name && null !== $data->host) {
            try {
                $params = (array) $data;
                unset($params['name']);
                $params['driver'] = Drivers::getDriver($data->driver);
                $params['driverOptions'] = [
                    PDO::ATTR_TIMEOUT => 5,
                ];
                DriverManager::getConnection($params)->getNativeConnection();
            } catch (Throwable $e) {
                $executionContext->addViolation($e->getMessage());
            }
        }
    }
}
