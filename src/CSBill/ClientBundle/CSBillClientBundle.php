<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CSBillClientBundle extends Bundle
{
    /**
     * Extends the CSClientBundle, so we can override some of the features specific to this application
     */
    public function getParent()
    {
        return 'CSClientBundle';
    }
}
