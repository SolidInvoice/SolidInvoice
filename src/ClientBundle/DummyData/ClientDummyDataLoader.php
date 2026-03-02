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

namespace SolidInvoice\ClientBundle\DummyData;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Faker\Factory;
use Faker\Generator;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\DummyData\DummyDataLoaderInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use function assert;
use function random_int;
use function substr;

final class ClientDummyDataLoader implements DummyDataLoaderInterface
{
    private readonly Generator $faker;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
        $this->faker = Factory::create();
    }

    public static function getPriority(): int
    {
        return 90;
    }

    public function load(Company $company): void
    {
        $em = $this->registry->getManager();
        assert($em instanceof EntityManagerInterface);

        //$currencies = ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];
        $currencies = ['USD'];

        for ($i = 0; $i < 10; ++$i) {
            $client = new Client();
            $client->setName(substr($this->faker->company(), 0, 125))
                ->setWebsite(substr($this->faker->url(), 0, 125))
                ->setStatus(ClientStatus::Active)
                ->setCurrencyCode($currencies[array_rand($currencies)])
                ->setCompany($company);

            if ($this->faker->boolean(70)) {
                $client->setVatNumber($this->faker->numerify('VAT########'));
            }

            $contactCount = random_int(1, 3);
            for ($j = 0; $j < $contactCount; ++$j) {
                $contact = new Contact();
                $contact->setFirstName($this->faker->firstName())
                    ->setLastName($this->faker->lastName())
                    ->setEmail($this->faker->safeEmail())
                    ->setCompany($company);

                $client->addContact($contact);
            }

            $addressCount = random_int(1, 2);
            for ($k = 0; $k < $addressCount; ++$k) {
                $address = new Address();
                $address->setStreet1($this->faker->streetAddress())
                    ->setCity($this->faker->city())
                    ->setState($this->faker->lexify('??'))
                    ->setZip($this->faker->postcode())
                    ->setCountry($this->faker->countryCode())
                    ->setCompany($company);

                $client->addAddress($address);
            }

            $em->persist($client);
        }

        $em->flush();
    }
}
