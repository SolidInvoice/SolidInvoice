<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Twig\Extension;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;

class MoneyFormatterExtension extends \Twig_Extension
{
    /**
     * @var \CSBill\MoneyBundle\Formatter\MoneyFormatter
     */
    private $formatter;

    /**
     * @param \CSBill\MoneyBundle\Formatter\MoneyFormatter $formatter
     */
    public function __construct(MoneyFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('currencyFormatter', function () {
                return $this->formatter;
            }),
        ];
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('formatCurrency', function ($money) {
                return $money ? $this->formatter->format($money) : null;
            }),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currency_formatter';
    }
}
