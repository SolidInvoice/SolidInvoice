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

namespace SolidInvoice\TaxBundle\DummyData;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Faker\Generator;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoaderInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\TaxBundle\Entity\Tax;
use function assert;

final class TaxDummyDataLoader implements DummyDataLoaderInterface
{
    private readonly Generator $faker;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
        $this->faker = Factory::create();
    }

    public static function getPriority(): int
    {
        return 100;
    }

    public function load(Company $company): void
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        $taxRates = [
            ['name' => 'VAT', 'rate' => 20.0, 'type' => Tax::TYPE_INCLUSIVE],
            ['name' => 'GST', 'rate' => 15.0, 'type' => Tax::TYPE_EXCLUSIVE],
            ['name' => 'Sales Tax', 'rate' => 8.5, 'type' => Tax::TYPE_EXCLUSIVE],
            ['name' => $this->faker->word() . ' Tax', 'rate' => (float) $this->faker->randomFloat(1, 5.0, 25.0), 'type' => Tax::TYPE_EXCLUSIVE],
            ['name' => $this->faker->word() . ' Levy', 'rate' => (float) $this->faker->randomFloat(1, 1.0, 10.0), 'type' => Tax::TYPE_INCLUSIVE],
        ];

        foreach ($taxRates as $taxData) {
            $tax = new Tax();
            $tax->setName($taxData['name'])
                ->setRate($taxData['rate'])
                ->setType($taxData['type'])
                ->setCompany($company);

            $em->persist($tax);
        }

        $em->flush();
    }
}
