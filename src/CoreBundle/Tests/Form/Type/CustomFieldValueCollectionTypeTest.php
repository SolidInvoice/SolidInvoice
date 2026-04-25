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

use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/**
 * @group functional
 */
final class CustomFieldValueCollectionTypeTest extends KernelTestCase
{
    use Factories;
    use EnsureApplicationInstalled;

    public function testSubmitCreatesValue(): void
    {
        $company = CompanyFactory::createOne();
        $client = ClientFactory::createOne(['company' => $company])->_real();

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $field = (new CustomField())
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
}
