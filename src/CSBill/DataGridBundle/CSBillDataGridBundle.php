<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\DataGridBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CSBillDataGridBundle extends Bundle
{
    /**
     * (non-PHPdoc)
     * @see Symfony\Component\HttpKernel\Bundle.Bundle::getParent()
     */
    public function getParent()
    {
        return 'APYDataGridBundle';
    }
}
