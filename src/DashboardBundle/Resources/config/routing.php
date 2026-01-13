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

use SolidInvoice\DashboardBundle\Action\DismissOnboardingChecklist;
use SolidInvoice\DashboardBundle\Action\Index;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator
        ->add('_dashboard', '/dashboard')
        ->controller(Index::class);
    $routingConfigurator
        ->add('_dashboard_onboarding_dismiss', '/dashboard/onboarding/dismiss')
        ->controller(DismissOnboardingChecklist::class)
        ->methods(['POST'])
    ;
};
