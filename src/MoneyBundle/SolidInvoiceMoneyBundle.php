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

namespace SolidInvoice\MoneyBundle;

use SolidInvoice\MoneyBundle\Entity\Money;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SolidInvoiceMoneyBundle extends Bundle
{
    public function boot(): void
    {
        $currency = $this->container->get('currency');
        Money::setBaseCurrency($currency->getCode());
    }
}
