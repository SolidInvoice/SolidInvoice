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

namespace CSBill\ClientBundle\Serializer\Handler;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use Money\Money;

class CreditHandler
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @param MoneyFormatter $formatter
     */
    public function __construct(MoneyFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Money                    $money
     *
     * @return float
     */
    public function serializeMoneyJson(JsonSerializationVisitor $visitor, Money $money): float
    {
        return (float) $this->formatter->format($money);
    }

    /**
     * @param XmlSerializationVisitor $visitor
     * @param Money                   $money
     */
    public function serializeMoneyXml(XmlSerializationVisitor $visitor, Money $money)
    {
        /** @var \DOMElement $node */
        $node = $visitor->getCurrentNode();

        $node->nodeValue = $this->formatter->format($money);
    }
}
