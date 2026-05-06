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

namespace SolidInvoice\CoreBundle\DependencyInjection\Compiler;

use Override;
use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidWorx\Platform\PlatformBundle\Feature\SubscriberResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Overrides the SubscriberResolver alias to point at CompanySubscriberResolver.
 *
 * This runs as a compiler pass (after all extension load() calls) so it
 * takes precedence over the NullSubscriberResolver alias defined in PlatformBundle's
 * services.php, regardless of bundle registration order.
 */
final class SubscriberResolverPass implements CompilerPassInterface
{
    #[Override]
    public function process(ContainerBuilder $container): void
    {
        $container->setAlias(SubscriberResolver::class, CompanySubscriberResolver::class)
            ->setPublic(false);
    }
}
