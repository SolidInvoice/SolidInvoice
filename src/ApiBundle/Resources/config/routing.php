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

use SolidInvoice\ApiBundle\Action\Company\ShowCompanyLogo;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('api_login_check', '/login');

    $routingConfigurator->import(ShowCompanyLogo::class, 'attribute');
    $routingConfigurator->import('../../Action/Reporting/', 'attribute');

    $routingConfigurator->import('.', 'api_platform');
};
