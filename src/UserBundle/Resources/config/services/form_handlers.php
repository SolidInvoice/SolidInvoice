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

use SolidWorx\FormHandler\FormHandlerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->tag('form.handler');

    $services->instanceof(FormHandlerInterface::class)
        ->autowire(true)
        ->private();

    $services->load('SolidInvoice\UserBundle\Form\Handler\\', __DIR__ . '/../../../Form/Handler')
        ->lazy(true);
};
