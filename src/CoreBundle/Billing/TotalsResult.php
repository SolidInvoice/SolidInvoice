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

namespace SolidInvoice\CoreBundle\Billing;

use Brick\Math\BigDecimal;

final readonly class TotalsResult
{
    public function __construct(
        public BigDecimal $total,
        public BigDecimal $baseTotal,
        public BigDecimal $tax,
    ) {
    }
}
