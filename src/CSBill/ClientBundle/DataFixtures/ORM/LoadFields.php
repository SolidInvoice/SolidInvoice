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
use CSBill\ClientBundle\Entity\ContactType;

class LoadFields implements FixtureInterface
{
    /*
     * (non-phpdoc)
     */
    public function load(ObjectManager $manager)
    {
        $fields = array('email' => 1,
                        'phone' => 0,
                        'address' => 0);

        foreach ($fields as $field => $required) {
            $entity = new ContactType();
            $entity->setName($field)
                   ->setRequired($required);
            $manager->persist($entity);
        }

        // flush client contact types
        $manager->flush();
    }
}
