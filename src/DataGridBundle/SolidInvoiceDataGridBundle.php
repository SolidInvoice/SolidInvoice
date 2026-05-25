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

use Override;
use SolidInvoice\DataGridBundle\DependencyInjection\SolidInvoiceDataGridExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SolidInvoiceDataGridBundle extends Bundle
{
    public const NAMESPACE = __NAMESPACE__;

    #[Override]
    public function getContainerExtension(): SolidInvoiceDataGridExtension
    {
        return new SolidInvoiceDataGridExtension();
    }
}
