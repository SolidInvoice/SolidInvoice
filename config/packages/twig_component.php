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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'twig_component' => [
        'anonymous_template_directory' => 'components/',
        'defaults' => [
            'SolidInvoice\\ClientBundle\\Twig\\Components\\' => '@SolidInvoiceClient/Components',
            'SolidInvoice\\CoreBundle\\Twig\\Components\\' => '@SolidInvoiceCore/Components',
            'SolidInvoice\\DataGridBundle\\Twig\\Components\\' => '@SolidInvoiceDataGrid/Components',
            'SolidInvoice\\InstallBundle\\Twig\\Components\\' => '@SolidInvoiceInstall/Components',
            'SolidInvoice\\InvoiceBundle\\Twig\\Components\\' => '@SolidInvoiceInvoice/Components',
            'SolidInvoice\\NotificationBundle\\Twig\\Components\\' => '@SolidInvoiceNotification/Components',
            'SolidInvoice\\QuoteBundle\\Twig\\Components\\' => '@SolidInvoiceQuote/Components',
            'SolidInvoice\\SettingsBundle\\Twig\\Components\\' => '@SolidInvoiceSettings/Components',
            'SolidInvoice\\TaxBundle\\Twig\\Components\\' => '@SolidInvoiceTax/Components',
            'SolidInvoice\\PaymentBundle\\Twig\\Components\\' => '@SolidInvoicePayment/Components',
            'SolidInvoice\\UserBundle\\Twig\\Components\\' => '@SolidInvoiceUser/Components',
        ],
    ],
]);
