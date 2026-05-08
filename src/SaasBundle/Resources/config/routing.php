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

use SolidInvoice\SaasBundle\Action\CancelDowngradeAction;
use SolidInvoice\SaasBundle\Action\ChangePlanAction;
use SolidInvoice\SaasBundle\Action\ChoosePlanAction;
use SolidInvoice\SaasBundle\Action\ConfirmPlanChangeAction;
use SolidInvoice\SaasBundle\Action\SelectPlanAction;
use SolidInvoice\SaasBundle\Action\SubscriptionOverviewAction;
use SolidInvoice\SaasBundle\Controller\PaymentSuccess;
use SolidInvoice\SaasBundle\Controller\SubscribeController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('billing_index', '/')
        ->controller(SubscriptionOverviewAction::class)
        ->methods(['GET']);

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

    $routingConfigurator->add('saas_subscription_change', '/subscription/change')
        ->controller(ChangePlanAction::class)
        ->methods(['GET']);

    $routingConfigurator->add('saas_subscription_change_confirm', '/subscription/change/confirm')
        ->controller(ConfirmPlanChangeAction::class)
        ->methods(['POST']);

    $routingConfigurator->add('saas_subscription_cancel_downgrade', '/subscription/cancel-downgrade')
        ->controller(CancelDowngradeAction::class)
        ->methods(['POST']);
};
