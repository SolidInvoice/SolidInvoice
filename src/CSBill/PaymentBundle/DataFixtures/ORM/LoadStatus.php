<?php
/**
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\PaymentBundle\DataFixtures\ORM;

use CSBill\CoreBundle\DataFixtures\AbstractStatusLoader;

class LoadStatus extends AbstractStatusLoader
{
    /**
     * {@inheritdoc}
     */
    public function getStatusList()
    {
        return array(
            'unknown' => 'default',
            'failed' => 'danger',
            'suspended' => 'warning',
            'expired' => 'danger',
            'captured' => 'success',
            'pending' => 'warning',
            'canceled' => 'inverse',
            'new' => 'info',
            'authorized' => 'info',
            'refunded' => 'warning',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusClass()
    {
        return 'CSBill\PaymentBundle\Entity\Status';
    }
}
