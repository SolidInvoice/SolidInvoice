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

namespace SolidInvoice\CoreBundle\Tests\Company;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\SettingsBundle\Entity\Setting;

final class DefaultDataTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testDefaultData(): void
    {
        $registry = M::mock(ManagerRegistry::class);
        $entityManager = M::mock(EntityManagerInterface::class);

        $registry
            ->expects('getManager')
            ->andReturn($entityManager);

        $entityManager
            ->expects('persist')
            ->with(M::type(Setting::class))
            ->times(20);

        $entityManager
            ->expects('persist')
            ->with(M::type(ContactType::class))
            ->times(3);

        $entityManager->expects('persist')
            ->with(M::type(PaymentMethod::class))
            ->times(3);

        $entityManager
            ->expects('flush')
            ->once();

        $defaultData = new DefaultData($registry);

        $company = new Company();
        $company->setName('Test Company');

        $defaultData->__invoke($company, ['currency' => 'EUR']);
    }
}
