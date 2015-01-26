<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\DataFixtures\ORM;

use CSBill\CoreBundle\DataFixtures\AbstractStatusLoader;

class LoadStatus extends AbstractStatusLoader
{
    /**
     * {@inheritdoc}
     */
    public function getStatusList()
    {
        return array(
            'active' => 'success',
            'inactive' => 'warning',
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusClass()
    {
        return 'CSBill\ClientBundle\Entity\Status';
    }
}
