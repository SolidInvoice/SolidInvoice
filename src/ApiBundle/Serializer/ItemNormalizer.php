<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Serializer;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Money\Money;

class ItemNormalizer
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    public function __construct(MoneyFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    public function normalizeCredit($credit): string
    {
        return $this->formatter->format(new Money((int) $credit['value']['amount'], new Currency($credit['value']['currency'])));
    }

    public function normalizeAdditionalContactDetails($details): array
    {
        if (empty($details)) {
            return [];
        }

        foreach ($details as &$value) {
            $value['type'] = $value['type']['name'];
        }

        return $details;
    }
}