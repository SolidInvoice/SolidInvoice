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

use SolidInvoice\SaasBundle\Action\OneTap\IssueNonceAction;
use SolidInvoice\SaasBundle\Action\OneTap\LoginCheckAction;
use SolidInvoice\SaasBundle\Action\OneTap\VerifyOneTapAction;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->add('onetap_nonce', '/nonce')
        ->controller(IssueNonceAction::class)
        ->methods(['GET']);

    $routingConfigurator->add('onetap_verify', '/verify')
        ->controller(VerifyOneTapAction::class)
        ->methods(['POST']);

    $routingConfigurator->add('_onetap_login_check', '/login-check')
        ->controller(LoginCheckAction::class)
        ->methods(['GET']);
};
