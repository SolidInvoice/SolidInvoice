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

namespace SolidInvoice\McpBundle\DependencyInjection;

use SolidInvoice\McpBundle\Mcp\Session\SafeSessionFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Forces {@see \SolidInvoice\McpBundle\Mcp\Session\SafeSessionFactory} onto
 * the upstream `mcp.server.builder` definition's setSession() call so the SDK
 * uses our crash-safe Session subclass. See {@see \SolidInvoice\McpBundle\Mcp\Session\SafeSession}
 * for the underlying bug.
 */
final class SafeSessionFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('mcp.server.builder')) {
            return;
        }

        $builder = $container->getDefinition('mcp.server.builder');
        $factoryRef = new Reference(SafeSessionFactory::class);
        $methodCalls = $builder->getMethodCalls();
        $patched = false;

        foreach ($methodCalls as $i => [$method, $args]) {
            if ($method !== 'setSession') {
                continue;
            }

            // Builder::setSession(SessionStoreInterface, SessionFactoryInterface = new SessionFactory(), int $ttl = 3600).
            // Insert our factory as the second argument, preserving any TTL the
            // upstream config may have set later.
            $args[1] = $factoryRef;
            $methodCalls[$i] = [$method, $args];
            $patched = true;

            break;
        }

        if (! $patched) {
            $methodCalls[] = ['setSession', [new Reference('mcp.session.store'), $factoryRef]];
        }

        $builder->setMethodCalls($methodCalls);
    }
}
