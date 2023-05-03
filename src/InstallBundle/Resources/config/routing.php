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

use SolidInvoice\InstallBundle\Action\Config;
use SolidInvoice\InstallBundle\Action\Finish;
use SolidInvoice\InstallBundle\Action\Install;
use SolidInvoice\InstallBundle\Action\Setup;
use SolidInvoice\InstallBundle\Action\SystemRequirements;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_install_check_requirements', '/install')
        ->controller(SystemRequirements::class);

    $routingConfigurator
        ->add('_install_config', '/install/config')
        ->controller(Config::class);

    $routingConfigurator
        ->add('_install_install', '/install/install')
        ->controller(Install::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_install_setup', '/install/setup')
        ->controller(Setup::class);

    $routingConfigurator
        ->add('_install_finish', '/install/finish')
        ->controller(Finish::class);
};
