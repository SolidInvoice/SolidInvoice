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
use SolidInvoice\CoreBundle\Action\DeleteCompany;
use SolidInvoice\CoreBundle\Action\Search;
use SolidInvoice\CoreBundle\Action\SearchSuggestions;
use SolidInvoice\CoreBundle\Action\SelectCompany;
use SolidInvoice\CoreBundle\Action\ViewBilling;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_home', '/')
        ->controller([RedirectController::class, 'redirectAction'])
        ->defaults(
            [
                'route' => '_dashboard',
                'permanent' => true,
            ]
        );

    $routingConfigurator
        ->add('_view_quote_external', '/view/quote/{uuid}.{_format}')
        ->controller([ViewBilling::class, 'quoteAction'])
        ->defaults(['_format' => 'html'])
        ->requirements(['uuid' => '[a-zA-Z0-9-]{36}', '_format' => 'html|pdf']);

    $routingConfigurator
        ->add('_view_invoice_external', '/view/invoice/{uuid}.{_format}')
        ->controller([ViewBilling::class, 'invoiceAction'])
        ->defaults(['_format' => 'html'])
        ->requirements(['uuid' => '[a-zA-Z0-9-]{36}', '_format' => 'html|pdf']);

    $routingConfigurator
        ->add('_select_company', '/select-company')
        ->controller(SelectCompany::class);

    $routingConfigurator
        ->add('_switch_company', '/select-company/{id}')
        ->controller([SelectCompany::class, 'switchCompany']);

    $routingConfigurator
        ->add('_create_company', '/create-company')
        ->controller(CreateCompany::class);

    $routingConfigurator
        ->add('_delete_company', '/delete-company')
        ->controller(DeleteCompany::class)
        ->methods(['POST'])
    ;

    $routingConfigurator
        ->add('_search', '/search')
        ->controller(Search::class)
        ->methods(['GET']);

    $routingConfigurator
        ->add('_search_suggestions', '/search/suggestions')
        ->controller(SearchSuggestions::class)
        ->methods(['GET']);
};
