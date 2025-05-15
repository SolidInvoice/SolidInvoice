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

use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Config\SolidWorxPlatformSaasConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (SolidWorxPlatformSaasConfig $config): void {
    $config->doctrine()
        ->subscriptions()
        ->entity(Company::class);

    $config->payment()
        ->returnRoute('saas_payment_success');

    $config->integration()
        ->payment()
        ->lemonSqueezy()
        ->enabled(true)
        ->apiKey(env('SOLIDINVOICE_LEMON_SQUEEZY_API_KEY'))
        ->webhookSecret(env('SOLIDINVOICE_LEMON_SQUEEZY_WEBHOOK_SECRET'))
        ->storeId(env('SOLIDINVOICE_LEMON_SQUEEZY_STORE_ID'));
};
