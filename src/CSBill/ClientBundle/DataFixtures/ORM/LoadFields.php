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
