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

use Http\Discovery\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerBuilder): void {
    $services = $containerBuilder->services();

    $services->defaults()
        ->autoconfigure()
        ->private();

    $services->set(Psr17Factory::class);

    $services->alias(RequestFactoryInterface::class, Psr17Factory::class);
    $services->alias(ResponseFactoryInterface::class, Psr17Factory::class);
    $services->alias(ServerRequestFactoryInterface::class, Psr17Factory::class);
    $services->alias(StreamFactoryInterface::class, Psr17Factory::class);
    $services->alias(UploadedFileFactoryInterface::class, Psr17Factory::class);
    $services->alias(UriFactoryInterface::class, Psr17Factory::class);
};
