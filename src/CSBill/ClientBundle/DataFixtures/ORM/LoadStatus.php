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

use CSBill\ClientBundle\Entity\Status;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadStatus implements FixtureInterface
{
    /*
     * (non-phpdoc)
     */
    public function load(ObjectManager $manager)
    {
        // Active
        $active = new Status();
        $active->setName('active')
               ->setLabel('success');
        $manager->persist($active);

        // InActive
        $inActive = new Status();
        $inActive->setName('inactive')
                 ->setLabel('warning');

        $manager->persist($inActive);

        // flush client statuses
        $manager->flush();
    }
}
