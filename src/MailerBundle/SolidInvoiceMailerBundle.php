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

namespace SolidInvoice\MailerBundle;

use SolidInvoice\MailerBundle\DependencyInjection\CompilerPass\MailerTransportConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SolidInvoiceMailerBundle extends Bundle
{
    final public const NAMESPACE = __NAMESPACE__;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MailerTransportConfigCompilerPass());
    }
}
