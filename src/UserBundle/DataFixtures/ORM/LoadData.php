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

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CoreBundle\DataFixtures\LoadData as CoreFixture;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createMany(20);
        UserFactory::createOne(
            [
                'username' => 'test@example.com',
                'email' => 'test@example.com',
                'password' => (new NativePasswordHasher())->hash('Password1'),
            ]
        );
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [
            CoreFixture::class,
        ];
    }
}
