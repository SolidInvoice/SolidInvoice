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

use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidInvoice\UserBundle\Repository\UserRepositoryInterface;
use SolidInvoice\UserBundle\SolidInvoiceUserBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private();

    $services
        ->load(SolidInvoiceUserBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceUserBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    $services->alias(UserRepositoryInterface::class, UserRepository::class);

    $services
        ->load(SolidInvoiceUserBundle::NAMESPACE . '\\DataFixtures\\ORM\\', dirname(__DIR__, 3) . '/DataFixtures/ORM/*')
        ->tag('doctrine.fixture.orm');
};
