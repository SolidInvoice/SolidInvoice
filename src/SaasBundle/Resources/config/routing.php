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

use SolidInvoice\SaasBundle\Action\ChoosePlanAction;
use SolidInvoice\SaasBundle\Action\SelectPlanAction;
use SolidInvoice\SaasBundle\Controller\BillingController;
use SolidInvoice\SaasBundle\Controller\PaymentSuccess;
use SolidInvoice\SaasBundle\Controller\SubscribeController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('billing_index', '/')
        ->controller(BillingController::class);

    $routingConfigurator->add('saas_payment_success', '/payment/success')
        ->controller(PaymentSuccess::class);

    $routingConfigurator->add('saas_subscription_checkout', '/subscription/activate')
        ->controller(SubscribeController::class);

    $routingConfigurator->add('saas_subscription_plans', '/subscription/plans')
        ->controller(SelectPlanAction::class)
        ->methods(['GET']);

    $routingConfigurator->add('saas_subscription_choose', '/subscription/plans/choose')
        ->controller(ChoosePlanAction::class)
        ->methods(['POST']);
};
