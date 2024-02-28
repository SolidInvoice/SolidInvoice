<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;

final class LoadData extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        CompanyFactory::createMany(3);
    }
}
