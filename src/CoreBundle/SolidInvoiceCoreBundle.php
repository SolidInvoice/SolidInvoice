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

namespace SolidInvoice\CoreBundle;

use SolidInvoice\CoreBundle\DependencyInjection\Compiler\DbalLoggerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SolidInvoiceCoreBundle extends Bundle
{
    public const VERSION = '2.2.0-dev';

    public const APP_NAME = 'SolidInvoice';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new DbalLoggerPass());
    }
}
