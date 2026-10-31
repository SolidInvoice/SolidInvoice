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

namespace SolidInvoice\PaymentBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidInvoice\PaymentBundle\Enum\PaymentMeans;
use SolidInvoice\PaymentBundle\Repository\PaymentInstructionRepository;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentInstructionFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(PaymentInstructionRepository::class)]
final class PaymentInstructionRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private PaymentInstructionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $em->getRepository(PaymentInstruction::class);
    }

    public function testFindCompanyInstructionsOrdersDefaultFirstThenLabel(): void
    {
        PaymentInstructionFactory::createOne([
            'company' => $this->company,
            'label' => 'Zelle account',
            'default' => false,
        ]);
        $default = PaymentInstructionFactory::createOne([
            'company' => $this->company,
            'label' => 'EUR account',
            'default' => true,
        ]);
        $other = PaymentInstructionFactory::createOne([
            'company' => $this->company,
            'label' => 'Business account',
            'default' => false,
        ]);

        $result = $this->repository->findCompanyInstructions($this->company->getId());

        self::assertCount(3, $result);
        self::assertSame($default->getId()->toRfc4122(), $result[0]->getId()->toRfc4122());
        self::assertSame($other->getId()->toRfc4122(), $result[1]->getId()->toRfc4122());
    }

    /**
     * Multi-tenancy regression: a second company's payment instruction must never leak
     * into the first company's result set.
     */
    public function testFindCompanyInstructionsDoesNotReturnAnotherCompanysInstructions(): void
    {
        PaymentInstructionFactory::createOne([
            'company' => $this->company,
            'label' => 'Business account',
        ]);

        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        PaymentInstructionFactory::createOne([
            'company' => $otherCompany,
            'label' => 'Other company account',
            'means' => PaymentMeans::Other,
        ]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $result = $this->repository->findCompanyInstructions($this->company->getId());

        self::assertCount(1, $result);
        self::assertSame('Business account', $result[0]->getLabel());
    }
}
