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

return static function (ContainerConfigurator $containerBuilder): void {
    $services = $containerBuilder->services();

    $services->defaults()
        ->autoconfigure()
        ->private();

    $services->set(Http\Discovery\Psr17Factory::class);

    $services->alias(Psr\Http\Message\RequestFactoryInterface::class, Http\Discovery\Psr17Factory::class);
    $services->alias(Psr\Http\Message\ResponseFactoryInterface::class, Http\Discovery\Psr17Factory::class);
    $services->alias(Psr\Http\Message\ServerRequestFactoryInterface::class, Http\Discovery\Psr17Factory::class);
    $services->alias(Psr\Http\Message\StreamFactoryInterface::class, Http\Discovery\Psr17Factory::class);
    $services->alias(Psr\Http\Message\UploadedFileFactoryInterface::class, Http\Discovery\Psr17Factory::class);
    $services->alias(Psr\Http\Message\UriFactoryInterface::class, Http\Discovery\Psr17Factory::class);
};
