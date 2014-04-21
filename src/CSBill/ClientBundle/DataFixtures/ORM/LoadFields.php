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
        $email = new ContactType();
        $email->setName('email')
              ->setType('email')
              ->setRequired(true)
              ->setOptions(array(
                'constraints' => array(
                    'email'
                )
            ));
        $manager->persist($email);

        $mobile = new ContactType();
        $mobile->setName('mobile')
               ->setType('text');
        $manager->persist($mobile);

        $phone = new ContactType();
        $phone->setName('phone')
              ->setType('text');
        $manager->persist($phone);

        $address = new ContactType();
        $address->setName('address')
                ->setType('textarea');
        $manager->persist($address);

        // flush client contact types
        $manager->flush();
    }
}
