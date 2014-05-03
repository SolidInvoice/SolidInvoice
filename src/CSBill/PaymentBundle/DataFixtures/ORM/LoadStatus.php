<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\PaymentBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CSBill\PaymentBundle\Entity\Status;

class LoadStatus implements FixtureInterface
{
    /*
     * (non-phpdoc)
     */
    public function load(ObjectManager $manager)
    {
        $statusList = array(
            array(
                'name' => 'unknown',
                'label'  => 'default'
            ),
            array(
                'name' => 'failed',
                'label'  => 'danger'
            ),
            array(
                'name' => 'suspended',
                'label'  => 'warning'
            ),
            array(
                'name' => 'expired',
                'label'  => 'danger'
            ),
            array(
                'name' => 'success',
                'label'  => 'success'
            ),
            array(
                'name' => 'pending',
                'label'  => 'warning'
            ),
            array(
                'name' => 'canceled',
                'label'  => 'inverse'
            ),
            array(
                'name' => 'new',
                'label'  => 'info'
            ),
        );

        foreach ($statusList as $status) {
            $entity = new Status();
            $entity->setName($status['name'])
                ->setLabel($status['label']);
            $manager->persist($entity);
        }

        // flush statuses
        $manager->flush();
    }
}
