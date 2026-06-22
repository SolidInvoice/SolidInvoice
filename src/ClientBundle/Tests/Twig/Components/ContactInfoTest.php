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
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(ContactInfo::class)]
final class ContactInfoTest extends LiveComponentTest
{
    use Factories;

    public function testSaveExistingContactPersistsCustomFieldValue(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company])->_real();
        $contact = ContactFactory::createOne([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'client' => $client,
            'company' => $this->company,
        ])->_real();

        $component = $this->createLiveComponent(
            name: ContactInfo::class,
            data: ['contact' => $contact],
            client: $this->client,
        )->actingAs($this->getUser());

        $component->set('contact', [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'jane@example.com',
            'customFields' => ['phone' => '9876543210'],
        ])->call('save');

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CONTACT, $contact->getId());
        self::assertCount(1, $values);
        self::assertSame('9876543210', $values[0]->getValue());
    }
}
