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

namespace SolidInvoice\DataGridBundle;

use SolidInvoice\DataGridBundle\DependencyInjection\CompilerPass\GridDefinitionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

class SolidInvoiceDataGridBundle extends Bundle
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new GridDefinitionCompilerPass($this->kernel));
    }
}
