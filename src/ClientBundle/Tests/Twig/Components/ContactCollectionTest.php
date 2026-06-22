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

namespace SolidInvoice\ClientBundle\Tests\Twig\Components;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Twig\Components\ContactCollection;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ContactCollection::class)]
final class ContactCollectionTest extends LiveComponentTest
{
    use Factories;

    public function testSaveNewContactPersistsCustomFieldValue(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company])->_real();

        $component = $this->createLiveComponent(
            name: ContactCollection::class,
            data: ['client' => $client],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('contact', [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'customFields' => ['phone' => '1234567890'],
        ])->call('save');

        $contactRepository = self::getContainer()->get('doctrine')->getRepository(Contact::class);
        $contact = $contactRepository->findOneBy(['email' => 'john@example.com']);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertNotNull($contact->getId());

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CONTACT, $contact->getId());
        self::assertCount(1, $values);
        self::assertSame('1234567890', $values[0]->getValue());
    }
}
