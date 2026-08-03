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

namespace SolidInvoice\ClientBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A client's contacts are rendered into invoice and quote forms, so the order they come
 * back in is user-visible and ends up in snapshots. Without an explicit order the database
 * chooses, and PostgreSQL does not choose insertion order.
 */
#[Group('functional')]
final class ContactOrderingTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testContactsAreReturnedInInsertionOrder(): void
    {
        $client = ClientFactory::createOne(['name' => 'Ordering Test', 'currencyCode' => 'USD']);

        $expected = [];

        foreach (['Aaron', 'Zoe', 'Mia', 'Ben'] as $firstName) {
            $contact = ContactFactory::createOne([
                'firstName' => $firstName,
                'lastName' => 'Example',
                'email' => strtolower($firstName) . '@example.com',
                'client' => $client,
            ]);

            $expected[] = $contact->getId()->toRfc4122();
        }

        $id = $client->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Client::class, $id);

        self::assertInstanceOf(Client::class, $reloaded);

        $actual = array_map(
            static fn ($contact): string => $contact->getId()->toRfc4122(),
            $reloaded->getContacts()->toArray()
        );

        // Ids are ULIDs, so insertion order is id order; the point is that it is stable at
        // all, on every platform, rather than whatever the query planner returns.
        self::assertSame($expected, array_values($actual));
    }
}
