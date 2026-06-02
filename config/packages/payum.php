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

use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidInvoice\PaymentBundle\Form\Methods\AuthorizeNetAim;
use SolidInvoice\PaymentBundle\Form\Methods\Be2billDirect;
use SolidInvoice\PaymentBundle\Form\Methods\Be2billOffsite;
use SolidInvoice\PaymentBundle\Form\Methods\KlarnaCheckout;
use SolidInvoice\PaymentBundle\Form\Methods\KlarnaInvoice;
use SolidInvoice\PaymentBundle\Form\Methods\Payex;
use SolidInvoice\PaymentBundle\Form\Methods\PaypalExpressCheckout;
use SolidInvoice\PaymentBundle\Form\Methods\PaypalProCheckout;
use SolidInvoice\PaymentBundle\Form\Methods\StripeCheckout;
use SolidInvoice\PaymentBundle\Form\Methods\StripeJs;

return App::config([
    'parameters' => [
        'payum.template.layout' => '@SolidInvoicePayment/layout.html.twig',
    ],
    'payum' => [
        'security' => [
            'token_storage' => [
                SecurityToken::class => [
                    'doctrine' => 'orm',
                ],
            ],
        ],
        'storages' => [
            Payment::class => [
                'doctrine' => 'orm',
            ],
        ],
        'dynamic_gateways' => [
            'sonata_admin' => false,
            'config_storage' => [
                PaymentMethod::class => [
                    'doctrine' => 'orm',
                ],
            ],
        ],
    ],
    'payment' => [
        'gateways' => [
            ['name' => 'credit', 'factory' => 'offline'],
            ['name' => 'custom', 'factory' => 'offline'],
            ['name' => 'cash', 'factory' => 'offline'],
            ['name' => 'bank_transfer', 'factory' => 'offline'],
            ['name' => 'paypal_express_checkout', 'factory' => 'paypal_express_checkout', 'form' => PaypalExpressCheckout::class],
            ['name' => 'paypal_pro_checkout', 'factory' => 'paypal_pro_checkout', 'form' => PaypalProCheckout::class],
            ['name' => 'stripe_checkout', 'factory' => 'stripe_checkout', 'form' => StripeCheckout::class],
            ['name' => 'stripe_js', 'factory' => 'stripe_js', 'form' => StripeJs::class],
            ['name' => 'klarna_invoice', 'factory' => 'klarna_invoice', 'form' => KlarnaInvoice::class],
            ['name' => 'klarna_checkout', 'factory' => 'klarna_checkout', 'form' => KlarnaCheckout::class],
            ['name' => 'be2bill_offsite', 'factory' => 'be2bill_offsite', 'form' => Be2billOffsite::class],
            ['name' => 'be2bill_direct', 'factory' => 'be2bill_direct', 'form' => Be2billDirect::class],
            ['name' => 'authorize_net_aim', 'factory' => 'authorize_net_aim', 'form' => AuthorizeNetAim::class],
            ['name' => 'payex', 'factory' => 'payex', 'form' => Payex::class],
        ],
    ],
]);
