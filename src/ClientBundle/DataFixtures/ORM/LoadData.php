<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2019
 */

namespace SolidInvoice\ClientBundle\DataFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;

class LoadData extends Fixture
{
    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $client = new Client();
        $client->setName('Test');

        $contact = new Contact();
        $contact->setFirstName('Test');
        $contact->setEmail('test@example.com');
        $client->addContact($contact);

        $this->setReference('client', $client);
        $this->setReference('contact', $contact);

        $manager->persist($contact);
        $manager->persist($client);
        $manager->flush();
    }
}
