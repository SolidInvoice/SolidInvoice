<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use CSBill\UserBundle\Entity\Role;

class LoadRoles implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        //ROLE_SUPER_ADMIN
        $super_admin = new Role();
        $super_admin->setName('super_admin');
        $super_admin->setRole('ROLE_SUPER_ADMIN');
        $manager->persist($super_admin);

        // ROLE_ADMIN
        $admin = new Role();
        $admin->setName('admin');
        $admin->setRole('ROLE_ADMIN');
        $manager->persist($admin);

        // ROLE_CLIENT
        $client = new Role();
        $client->setName('client');
        $client->setRole('ROLE_CLIENT');
        $manager->persist($client);

        // ROLE_USER
        $user = new Role();
        $user->setName('user');
        $user->setRole('ROLE_USER');
        $manager->persist($user);

        $manager->flush();
    }
}
