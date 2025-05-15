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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set('env(SOLIDINVOICE_LEMON_SQUEEZY_API_KEY)', null);
    $parameters->set('env(SOLIDINVOICE_LEMON_SQUEEZY_STORE_ID)', null);
    $parameters->set('env(SOLIDINVOICE_LEMON_SQUEEZY_WEBHOOK_SECRET)', '');
};
