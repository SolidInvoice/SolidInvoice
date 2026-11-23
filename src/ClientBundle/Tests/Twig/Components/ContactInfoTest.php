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
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\ClientBundle\Twig\Components\ContactInfo;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\LiveComponentTest;

#[CoversClass(ContactInfo::class)]
final class ContactInfoTest extends LiveComponentTest
{
    public function testSaveExistingContactPersistsCustomFieldValue(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company]);
        $contact = ContactFactory::createOne([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'client' => $client,
            'company' => $this->company,
        ]);

        $component = $this->createLiveComponent(
            name: ContactInfo::class,
            data: ['contact' => $contact],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('contact', [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'customFields' => ['additional_email' => 'accounts@example.com'],
        ])->call('save');

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CONTACT, $contact->getId());
        self::assertCount(1, $values);
        self::assertSame('accounts@example.com', $values[0]->getValue());
    }
}
