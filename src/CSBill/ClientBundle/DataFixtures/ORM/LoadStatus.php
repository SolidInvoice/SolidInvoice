<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CSBill\ClientBundle\Entity\Status;

class LoadStatus implements FixtureInterface
{
    /*
     * (non-phpdoc)
     */
    public function load(ObjectManager $manager)
    {
        // Active
        $active = new Status();
        $active->setName('active');
        $manager->persist($active);

        // InActive
        $inActive = new Status();
        $inActive->setName('inactive');
        $manager->persist($inActive);

        // flush client statuses
        $manager->flush();
    }
}
