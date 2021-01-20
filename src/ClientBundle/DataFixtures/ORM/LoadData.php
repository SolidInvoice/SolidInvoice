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
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;

/**
 * @codeCoverageIgnore
 */
class LoadData extends Fixture
{
    /**
     * {@inheritdoc}
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
