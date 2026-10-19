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

use SolidInvoice\InvoiceBundle\Action\CreditNote\Create;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Delete;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Edit;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Index;
use SolidInvoice\InvoiceBundle\Action\CreditNote\Transition;
use SolidInvoice\InvoiceBundle\Action\CreditNote\View;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_credit_notes_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_credit_notes_create', '/create/{client}')
        ->controller(Create::class)
        ->defaults(['client' => null]);

    $routingConfigurator
        ->add('_credit_notes_edit', '/{id}/edit')
        ->controller(Edit::class);

    $routingConfigurator
        ->add('_credit_notes_view', '/{id}')
        ->methods(['GET'])
        ->controller(View::class);

    $routingConfigurator
        ->add('_credit_notes_transition', '/{id}/transition/{action}')
        ->methods(['POST'])
        ->controller(Transition::class);

    $routingConfigurator
        ->add('_credit_notes_delete', '/{id}')
        ->methods(['DELETE', 'POST'])
        ->controller(Delete::class);
};
