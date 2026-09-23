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

use SolidInvoice\EInvoiceBundle\Profile\ProfileInterface;
use SolidInvoice\EInvoiceBundle\Profile\ProfileRegistry;
use SolidInvoice\EInvoiceBundle\SolidInvoiceEInvoiceBundle;
use SolidInvoice\EInvoiceBundle\Validation\ValidatorInterface;
use SolidInvoice\EInvoiceBundle\Validation\ValidatorRegistry;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services
        ->load(SolidInvoiceEInvoiceBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services->set(ProfileRegistry::class)
        ->arg('$profiles', tagged_iterator(ProfileInterface::DI_TAG));

    $services->set(ValidatorRegistry::class)
        ->arg('$validators', tagged_iterator(ValidatorInterface::DI_TAG));
};
