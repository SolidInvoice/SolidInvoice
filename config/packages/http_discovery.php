<?php

declare(strict_types=1);

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
