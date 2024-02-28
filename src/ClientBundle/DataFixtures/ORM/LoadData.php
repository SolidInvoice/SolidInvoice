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

namespace SolidInvoice\ClientBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\AdditionalContactDetailFactory;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\DataFixtures\LoadData as CoreFixture;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $clients = ClientFactory::createMany(100, static function () {
            $company = CompanyFactory::random();
            return [
                'company' => $company,
            ];
        });

        foreach ($clients as $client) {
            self::getContactsFactory($client->getCompany(), $client);
        }
    }

    /**
     * @return list<class-string>
     */
    public function getDependencies(): array
    {
        return [
            CoreFixture::class
        ];
    }

    /**
     * @param Proxy<Client> $client
     */
    private static function getContactsFactory(Company $company, Proxy $client): void
    {
        $contacts = ContactFactory::new(static function () use ($company, $client) {
            return [
                'client' => $client,
                'company' => $company,
            ];
        })->many(1, 5);

        foreach ($contacts as $contact) {
            self::addAdditionalContactDetails($company, $contact->create());
        }
    }

    /**
     * @param Proxy<Contact> $contact
     */
    private static function addAdditionalContactDetails(Company $company, Proxy $contact): void
    {
        AdditionalContactDetailFactory::new(static function () use ($company, $contact) {
            return [
                'contact' => $contact,
                'company' => $company,
            ];
        })->many(0, 5);
    }
}
