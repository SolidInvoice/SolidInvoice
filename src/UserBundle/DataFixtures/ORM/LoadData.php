<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\DataFixtures\ORM;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $company = $manager->getRepository(Company::class)->findOneBy([]);

        if (! $company instanceof Company) {
            throw new RuntimeException('No company found');
        }

        $user1 = (new User())
            ->setEmail('test1@test.com')
            ->setPassword('test1')
            ->setConfirmationToken(base64_encode(bin2hex(random_bytes(24))))
            ->setPasswordRequestedAt(new DateTime())
            ->addCompany($company)
        ;

        $user2 = (new User())
            ->setEmail('test2@test.com')
            ->setPassword('test2')
            ->setEnabled(true)
            ->addCompany($company)
        ;

        $manager->persist($user1);
        $manager->persist($user2);
        $manager->flush();

        $this->setReference('user1', $user1);
        $this->setReference('user2', $user2);
    }
}
