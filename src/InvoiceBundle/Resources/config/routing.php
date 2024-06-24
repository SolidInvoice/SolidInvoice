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

use SolidInvoice\InvoiceBundle\Action\CloneInvoice;
use SolidInvoice\InvoiceBundle\Action\CloneRecurringInvoice;
use SolidInvoice\InvoiceBundle\Action\Create;
use SolidInvoice\InvoiceBundle\Action\CreateRecurring;
use SolidInvoice\InvoiceBundle\Action\Edit;
use SolidInvoice\InvoiceBundle\Action\EditRecurring;
use SolidInvoice\InvoiceBundle\Action\Fields;
use SolidInvoice\InvoiceBundle\Action\Index;
use SolidInvoice\InvoiceBundle\Action\RecurringIndex;
use SolidInvoice\InvoiceBundle\Action\RecurringTransition;
use SolidInvoice\InvoiceBundle\Action\Transition;
use SolidInvoice\InvoiceBundle\Action\Transition\Send;
use SolidInvoice\InvoiceBundle\Action\View;
use SolidInvoice\InvoiceBundle\Action\ViewRecurring;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_invoices_index', '/')
        ->controller(Index::class);

    $routingConfigurator
        ->add('_invoices_index_recurring', '/recurring')
        ->controller(RecurringIndex::class);

    $routingConfigurator
        ->add('_invoices_create', '/create/{client}')
        ->controller(Create::class)
        ->defaults(['client' => null])
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_create_recurring', '/recurring/create/{client}')
        ->controller(CreateRecurring::class)
        ->defaults(['client' => null])
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_get_fields', '/fields/get/{currency}')
        ->controller(Fields::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_edit', '/edit/{id}')
        ->controller(Edit::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_edit_recurring', '/recurring/edit/{id}')
        ->controller(EditRecurring::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_view', '/view/{id}.{_format}')
        ->controller(View::class)
        ->defaults(['_format' => 'html'])
        ->requirements(['_format' => 'html|pdf'])
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_view_recurring', '/recurring/view/{id}')
        ->controller(ViewRecurring::class)
        ->options(['expose' => true]);

    $routingConfigurator
        ->add('_invoices_clone', '/clone/{id}')
        ->controller(CloneInvoice::class);

    $routingConfigurator
        ->add('_invoices_clone_recurring', '/clone-recurring/{id}')
        ->controller(CloneRecurringInvoice::class);

    $routingConfigurator
        ->add('_send_invoice', '/action/send/{id}')
        ->controller(Send::class);

    $routingConfigurator
        ->add('_action_invoice', '/action/{action}/{id}')
        ->controller(Transition::class);

    $routingConfigurator
        ->add('_action_recurring_invoice', '/recurring-action/{action}/{id}')
        ->controller(RecurringTransition::class);
};
