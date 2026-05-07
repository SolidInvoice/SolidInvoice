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

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\SaasBundle\SolidInvoiceSaasBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
    ;

    // Tag checklist items BEFORE load()
    $services
        ->instanceof(ChecklistItemInterface::class)
        ->tag('dashboard.checklist_item');

    $services
        ->load(SolidInvoiceSaasBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Message,Resources,Tests}');

    $services->alias(
        \SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface::class,
        \SolidInvoice\SaasBundle\Email\SaasEmailVerificationGate::class,
    );
};
