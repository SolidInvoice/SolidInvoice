<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InvoiceBundle\Model;

use CSBill\CoreBundle\Model\Status as AbstractStatus;

class Status extends AbstractStatus
{
    /**
     * Contains a list of all the statuses and their corresponding labels
     *
     * @var array
     */
    protected $statusLabels = array(
                                    'draft'     => 'draft',
                                    'pending'   => 'warning',
                                    'paid'      => 'success',
                                    'overdue'   => 'important',
                                    'cancelled' => 'inverse'
            );
}
