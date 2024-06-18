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

use Symfony\Config\TwigComponentConfig;

return static function (TwigComponentConfig $config): void {
    $config
        ->anonymousTemplateDirectory('components/')
        ->defaults('SolidInvoice\ClientBundle\Twig\Components\\', '@SolidInvoiceClient/Components')
        ->defaults('SolidInvoice\CoreBundle\Twig\Components\\', '@SolidInvoiceCore/Components')
        ->defaults('SolidInvoice\DataGridBundle\Twig\Components\\', '@SolidInvoiceDataGrid/Components')
        ->defaults('SolidInvoice\InvoiceBundle\Twig\Components\\', '@SolidInvoiceInvoice/Components')
        ->defaults('SolidInvoice\NotificationBundle\Twig\Components\\', '@SolidInvoiceNotification/Components')
        ->defaults('SolidInvoice\QuoteBundle\Twig\Components\\', '@SolidInvoiceQuote/Components')
        ->defaults('SolidInvoice\SettingsBundle\Twig\Components\\', '@SolidInvoiceSettings/Components')
        ->defaults('SolidInvoice\PaymentBundle\Twig\Components\\', '@SolidInvoicePayment/Components')
        ->defaults('SolidInvoice\UserBundle\Twig\Components\\', '@SolidInvoiceUser/Components')
    ;
};
