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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Company\DefaultData;
use SolidInvoice\CoreBundle\Config\SystemConfigProvider;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Config\ConfigProvider as InvoiceConfigProvider;
use SolidInvoice\MailerBundle\Config\ConfigProvider as MailerConfigProvider;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Config\ConfigProvider as QuoteConfigProvider;
use SolidInvoice\SettingsBundle\Entity\Setting;

final class DefaultDataTest extends TestCase
{
    public function testDefaultData(): void
    {
        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $registry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($entityManager);

        $entityManager
            ->expects($this->exactly(28))
            ->method('persist')
            ->with($this->logicalOr(
                $this->isInstanceOf(Setting::class),
                $this->isInstanceOf(ContactType::class),
                $this->isInstanceOf(PaymentMethod::class)
            ));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $defaultData = new DefaultData($registry, [
            new SystemConfigProvider(),
            new InvoiceConfigProvider(),
            new QuoteConfigProvider(),
            new MailerConfigProvider(),
        ]);

        $company = new Company();
        $company->setName('Test Company');

        $defaultData->__invoke($company, ['currency' => 'EUR']);
    }
}
