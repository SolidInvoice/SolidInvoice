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

use SolidInvoice\QuoteBundle\Action\CloneQuote;
use SolidInvoice\QuoteBundle\Action\Create;
use SolidInvoice\QuoteBundle\Action\Edit;
use SolidInvoice\QuoteBundle\Action\Fields;
use SolidInvoice\QuoteBundle\Action\Index;
use SolidInvoice\QuoteBundle\Action\Transition;
use SolidInvoice\QuoteBundle\Action\Transition\Send;
use SolidInvoice\QuoteBundle\Action\View;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_quotes_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_quotes_create', '/create/{client}')
        ->controller(Create::class)
        ->defaults(['client' => null])
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_quotes_get_fields', '/fields/get/{currency}')
        ->controller(Fields::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_quotes_edit', '/edit/{id}')
        ->controller(Edit::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_quotes_view', '/view/{id}.{_format}')
        ->controller(View::class)
        ->defaults(['_format' => 'html'])
        ->requirements(['_format' => 'html|pdf'])
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_quotes_clone', '/clone/{id}')
        ->controller(CloneQuote::class);

    $routingConfigurator
        ->add('_send_quote', '/action/send/{id}')
        ->controller(Send::class);

    $routingConfigurator
        ->add('_transition_quote', '/action/{action}/{id}')
        ->controller(Transition::class);
};
