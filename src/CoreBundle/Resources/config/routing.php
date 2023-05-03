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

use SolidInvoice\CoreBundle\Action\CreateCompany;
use SolidInvoice\CoreBundle\Action\SelectCompany;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('_home', '/')
        ->controller([
        RedirectController::class,
        'redirectAction',
    ])
        ->defaults([
        'route' => '_dashboard',
        'permanent' => true,
    ]);

    $routingConfigurator->add('_view_quote_external', '/view/quote/{uuid}')
        ->controller([
        ViewBilling::class,
        'quoteAction',
    ])
        ->requirements([
        'uuid' => '[a-zA-Z0-9-]{36}',
    ]);

    $routingConfigurator->add('_view_invoice_external', '/view/invoice/{uuid}')
        ->controller([
        ViewBilling::class,
        'invoiceAction',
    ])
        ->requirements([
        'uuid' => '[a-zA-Z0-9-]{36}',
    ]);

    $routingConfigurator->add('_select_company', '/select-company')
        ->controller(SelectCompany::class);

    $routingConfigurator->add('_switch_company', '/select-company/{id}')
        ->controller([
        SelectCompany::class,
        'switchCompany',
    ]);

    $routingConfigurator->add('_create_company', '/create-company')
        ->controller(CreateCompany::class);
};
