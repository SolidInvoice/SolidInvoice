<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\DataFixtures\ORM;

use CSBill\CoreBundle\DataFixtures\AbstractStatusLoader;

class LoadStatus extends AbstractStatusLoader
{
    /**
     * {@inheritdoc}
     */
    public function getStatusList()
    {
        return array(
            'draft' => 'default',
            'pending' => 'warning',
            'accepted' => 'success',
            'declined' => 'danger',
            'cancelled' => 'inverse',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusClass()
    {
        return 'CSBill\QuoteBundle\Entity\Status';
    }
}
