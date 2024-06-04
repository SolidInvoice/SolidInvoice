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

namespace SolidInvoice\NotificationBundle\DependencyInjection;

use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * @see \SolidInvoice\NotificationBundle\Tests\DependencyInjection\SolidInvoiceNotificationExtensionTest
 */
class SolidInvoiceNotificationExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->import('services/*.php');

        $container->registerAttributeForAutoconfiguration(AsNotification::class, static function (ChildDefinition $definition, AsNotification $notification, \Reflector $reflector): void {
            $definition->addTag('solid_invoice_notification.notification', ['name' => $notification->name]);
        });
    }
}
