<?php

declare(strict_types=1);

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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\PaymentConfig;
use Symfony\Config\PayumConfig;

return static function (PayumConfig $config, PaymentConfig $paymentConfig, ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('payum.template.layout', '@SolidInvoicePayment/layout.html.twig');

    $config
        ->security()
        ->tokenStorage(SecurityToken::class)
        ->doctrine('orm');

    $config
        ->storages(Payment::class)
        ->doctrine('orm');

    $config
        ->dynamicGateways()
        ->sonataAdmin(false)
        ->configStorage(PaymentMethod::class)
            ->doctrine('orm');

    $paymentConfig
        ->gateways('credit')
        ->factory('offline');

    $paymentConfig
        ->gateways('cash')
        ->factory('offline');

    $paymentConfig
        ->gateways('bank_transfer')
        ->factory('offline');

    $paymentConfig
        ->gateways('paypal_express_checkout')
        ->factory('paypal_express_checkout')
        ->form(PaypalExpressCheckout::class);

    $paymentConfig
        ->gateways('paypal_pro_checkout')
        ->factory('paypal_pro_checkout')
        ->form(PaypalProCheckout::class);

    $paymentConfig
        ->gateways('stripe_checkout')
        ->factory('stripe_checkout')
        ->form(StripeCheckout::class);

    $paymentConfig
        ->gateways('stripe_js')
        ->factory('stripe_js')
        ->form(StripeJs::class);

    $paymentConfig
        ->gateways('klarna_invoice')
        ->factory('klarna_invoice')
        ->form(KlarnaInvoice::class);

    $paymentConfig
        ->gateways('klarna_checkout')
        ->factory('klarna_checkout')
        ->form(KlarnaCheckout::class);

    $paymentConfig
        ->gateways('be2bill_offsite')
        ->factory('be2bill_offsite')
        ->form(Be2billOffsite::class);

    $paymentConfig
        ->gateways('be2bill_direct')
        ->factory('be2bill_direct')
        ->form(Be2billDirect::class);

    $paymentConfig
        ->gateways('authorize_net_aim')
        ->factory('authorize_net_aim')
        ->form(AuthorizeNetAim::class);

    $paymentConfig
        ->gateways('payex')
        ->factory('payex')
        ->form(Payex::class);
};
