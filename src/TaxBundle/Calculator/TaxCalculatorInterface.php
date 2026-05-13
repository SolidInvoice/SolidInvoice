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

namespace SolidInvoice\TaxBundle\Calculator;

use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\Result\CalculationResult;

interface TaxCalculatorInterface
{
    public function calculate(BaseInvoice|Quote $document, ?CalculationOptions $options = null): CalculationResult;
}
