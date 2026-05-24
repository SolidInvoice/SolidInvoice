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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Enum\CustomFieldType;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class CustomFieldValueCleanupListenerTest extends KernelTestCase
{
    use Factories;
    use EnsureApplicationInstalled;

    public function testValuesDeletedWhenClientIsRemoved(): void
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
        $value = new CustomFieldValue()
            ->setField($field)
            ->setTarget(CustomFieldTarget::CLIENT)
            ->setTargetId($client->getId())
            ->setValue('Sales')
            ->setCompany($company->_real());
        $em->persist($value);
        $em->flush();

        $clientId = $client->getId();

        $em->remove($client);
        $em->flush();
        $em->clear();

        /** @var CustomFieldValueRepository $repo */
        $repo = self::getContainer()->get(CustomFieldValueRepository::class);
        self::assertInstanceOf(Ulid::class, $clientId);
        self::assertSame([], $repo->findForRecord(CustomFieldTarget::CLIENT, $clientId));
    }
}
