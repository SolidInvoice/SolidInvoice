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

namespace SolidInvoice\CoreBundle\Billing\Discount;

use SolidInvoice\CoreBundle\Enum\TaxArithmeticVersion;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\MoneyBundle\Calculator;
use SolidInvoice\QuoteBundle\Entity\Quote;

final class DiscountTreatmentFactory
{
    public function for(BaseInvoice | Quote $document): DiscountTreatment
    {
        $version = $document->getTaxArithmeticVersion() ?? TaxArithmeticVersion::current();

        return match ($version) {
            TaxArithmeticVersion::Legacy => new LegacyDiscountTreatment(new Calculator()),
        };
    }
}
