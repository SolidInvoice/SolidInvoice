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

use SolidInvoice\ClientBundle\Action\Ajax\Address;
use SolidInvoice\ClientBundle\Action\Ajax\Address\AddressList;
use SolidInvoice\ClientBundle\Action\Ajax\Address\Delete;
use SolidInvoice\ClientBundle\Action\Ajax\Contact;
use SolidInvoice\ClientBundle\Action\Ajax\Contact\Add;
use SolidInvoice\ClientBundle\Action\Ajax\Contact\Edit;
use SolidInvoice\ClientBundle\Action\Ajax\Credit;
use SolidInvoice\ClientBundle\Action\Ajax\Info;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_xhr_clients_info', '/info/{id}/{type}')
        ->controller(Info::class)
        ->defaults(['type' => 'quote']);

    $routingConfigurator
        ->add('_xhr_clients_credit_get', '/credit/{client}')
        ->controller([Credit::class, 'get'])
        ->methods(['GET']);

    $routingConfigurator
        ->add('_xhr_clients_credit_update', '/credit/{client}')
        ->controller([Credit::class, 'put'])
        ->methods(['PUT']);

    $routingConfigurator
        ->add('_xhr_clients_add_contact', '/contacts/add/{id}')
        ->controller(Add::class);

    $routingConfigurator
        ->add('_xhr_clients_edit_contact', '/contacts/edit/{id}')
        ->controller(Edit::class)
        ->methods(['GET', 'POST']);

    $routingConfigurator
        ->add('_xhr_clients_address_list', '/address/list/{id}')
        ->controller(AddressList::class);

    $routingConfigurator
        ->add('_xhr_clients_edit_address', '/address/edit/{id}')
        ->controller(Address\Edit::class);

    $routingConfigurator
        ->add('_xhr_clients_delete_address', '/address/delete/{id}')
        ->controller(Delete::class);

    $routingConfigurator
        ->add('_xhr_clients_address', '/address/{id}')
        ->controller(Address::class)
        ->methods(['DELETE', 'GET']);

    $routingConfigurator
        ->add('_xhr_clients_contact', '/contacts/{id}')
        ->controller(Contact::class)
        ->methods(['GET', 'POST', 'DELETE']);

    $routingConfigurator
        ->add('_xhr_clients_delete_contact', '/contacts/delete/{id}')
        ->controller(Contact\Delete::class)
        ->methods(['DELETE']);

    $routingConfigurator
        ->add('_xhr_clients_delete', '/delete/{id}')
        ->controller(\SolidInvoice\ClientBundle\Action\Ajax\Delete::class)
        ->methods(['DELETE']);
};
