<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\NumberColumn;

class CurrencyColumn extends NumberColumn
{
    /**
     * @var string
     */
    private $currency;

    /**
     * @param string $currency
     */
    public function __construct($currency)
    {
        $this->currency = $currency;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        $params['currencyCode'] = $this->currency;
        $params['style'] = 'currency';

        parent::__initialize($params);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'currency';
    }
}