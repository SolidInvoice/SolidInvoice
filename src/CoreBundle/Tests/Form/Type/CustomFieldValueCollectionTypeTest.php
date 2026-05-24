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

namespace SolidInvoice\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class CustomFieldValueCollectionTypeTest extends KernelTestCase
{
    use Factories;
    use EnsureApplicationInstalled;

    public function testSubmitCreatesValue(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = new CustomField()
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company->_real());
        $em->persist($field);
        $em->flush();

        $form = self::getContainer()->get('form.factory')->create(
            CustomFieldValueCollectionType::class,
            null,
            ['target' => CustomFieldTarget::CLIENT, 'parent_record' => $client, 'csrf_protection' => false]
        );
        $form->submit(['department' => 'Sales']);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true));

        $em->flush();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CLIENT, $client->getId());
        self::assertCount(1, $values);
        self::assertSame('Sales', $values[0]->getValue());
    }

    public function testSubmitOnNewRecordCreatesValue(): void
    {
        $company = CompanyFactory::createOne();
        // CRITICAL: do NOT persist the client first — simulate the form-submit-then-persist flow.
        $client = new Client();
        $client->setName('Test')->setStatus(ClientStatus::Active)->setCompany($company->_real());

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = new CustomField()
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setLabel('Department')
            ->setFieldKey('department')
            ->setType(CustomFieldType::TEXT)
            ->setCompany($company->_real());
        $em->persist($field);
        $em->flush();

        $form = self::getContainer()->get('form.factory')->create(
            CustomFieldValueCollectionType::class,
            null,
            ['target' => CustomFieldTarget::CLIENT, 'parent_record' => $client, 'csrf_protection' => false]
        );
        $form->submit(['department' => 'Sales']);
        self::assertTrue($form->isValid(), (string) $form->getErrors(true));

        // Now the controller would call persist + flush:
        $em->persist($client);
        $em->flush();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        $values = $repo->findForRecord(CustomFieldTarget::CLIENT, $client->getId());
        self::assertCount(1, $values);
        self::assertSame('Sales', $values[0]->getValue());
    }

    public function testConstructorAssignedUlidPersists(): void
    {
        $company = CompanyFactory::createOne();
        $client = new Client();
        $client->setName('Test')->setStatus(ClientStatus::Active)->setCompany($company->_real());

        // Constructor pre-assigns a Ulid so getId() is non-null immediately.
        self::assertInstanceOf(Ulid::class, $client->getId());

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($client);
        $em->flush();

        // Doctrine assigns its own Ulid via UlidGenerator on persist, but
        // the entity does have a non-null ID after flush.
        $persistedId = $client->getId();
        self::assertInstanceOf(Ulid::class, $persistedId);
        $em->clear();

        $reloaded = $em->find(Client::class, $persistedId);
        self::assertNotNull($reloaded);
        self::assertTrue($persistedId->equals($reloaded->getId()));
    }
}
