<?php

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