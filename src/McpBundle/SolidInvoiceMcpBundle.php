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

namespace SolidInvoice\McpBundle;

use SolidInvoice\McpBundle\DependencyInjection\McpToolPublicPass;
use SolidInvoice\McpBundle\DependencyInjection\SafeSessionFactoryPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SolidInvoiceMcpBundle extends Bundle
{
    final public const string NAMESPACE = __NAMESPACE__;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Run after Symfony\AI\McpBundle\DependencyInjection\McpPass (priority 0)
        // so tool services already have their `mcp.tool` tag when we mark them public.
        $container->addCompilerPass(new McpToolPublicPass(), PassConfig::TYPE_BEFORE_REMOVING, -10);
        $container->addCompilerPass(new SafeSessionFactoryPass());
    }
}
