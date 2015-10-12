<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\NumberColumn;
use CSBill\MoneyBundle\Formatter\MoneyFormatter;

class CurrencyColumn extends NumberColumn
{
    /**
     * @var \CSBill\MoneyBundle\Formatter\MoneyFormatter
     */
    private $formatter;

    /**
     * @param MoneyFormatter $formatter
     */
    public function __construct(MoneyFormatter $formatter)
    {
        $this->formatter = $formatter;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        $params['style'] = 'currency';

        parent::__initialize($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayedValue($value)
    {
        return $this->formatter->format($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'currency';
    }
}
