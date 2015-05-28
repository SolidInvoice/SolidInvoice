<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Column;

use APY\DataGridBundle\Grid\Column\NumberColumn;

class PercentColumn extends NumberColumn
{
    /**
     * {@inheritdoc}
     */
    public function __initialize(array $params)
    {
        parent::__initialize($params);

        $this->callback = function ($value) {
            if (!empty($value)) {
                return ($value * 100).'%';
            }
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'percent';
    }
}
