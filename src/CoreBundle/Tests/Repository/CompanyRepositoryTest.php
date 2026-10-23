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

namespace SolidInvoice\CoreBundle\Tests\Repository;

use Payum\Core\Model\Identity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidInvoice\PaymentBundle\Test\Factory\PaymentFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use function str_repeat;

#[CoversClass(CompanyRepository::class)]
final class CompanyRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    public function testDeleteCompanyAlsoRemovesOrphanedSecurityTokens(): void
    {
        $companyId = $this->company->getId();
        $payment = PaymentFactory::createOne();

        $token = new SecurityToken();
        $token->setDetails(new Identity($payment->getId()->toString(), $payment));
        $token->setTargetUrl('https://example.com/payment');
        $token->setGatewayName('test_gateway');

        $this->em->persist($token);
        $this->em->flush();
        $this->em->clear();

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany($companyId);

        $this->em->clear();

        self::assertNull($this->em->find(Company::class, $companyId));
        self::assertCount(0, $this->em->getRepository(Payment::class)->findAll());
        self::assertCount(0, $this->em->getRepository(SecurityToken::class)->findAll());
    }

    public function testDeleteCompanyWithNoPaymentsDoesNotFail(): void
    {
        $companyId = $this->company->getId();

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany($companyId);

        $this->em->clear();

        self::assertNull($this->em->find(Company::class, $companyId));
    }

    public function testDeleteCompanyWithNullIdDoesNothing(): void
    {
        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->deleteCompany(null);

        self::assertNotNull($this->em->find(Company::class, $this->company->getId()));
    }

    public function testUpdateCompanyName(): void
    {
        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        $companyRepository->updateCompanyName('Acme Corp');

        $this->em->clear();

        self::assertSame('Acme Corp', $this->em->find(Company::class, $this->company->getId())?->getName());
    }

    #[DataProvider('invalidCompanyNames')]
    public function testUpdateCompanyNameRejectsInvalidName(?string $name): void
    {
        $originalName = $this->company->getName();

        /** @var CompanyRepository $companyRepository */
        $companyRepository = $this->em->getRepository(Company::class);

        try {
            $companyRepository->updateCompanyName($name);
            self::fail('Expected a ValidationFailedException');
        } catch (ValidationFailedException) {
        }

        $this->em->clear();

        self::assertSame($originalName, $this->em->find(Company::class, $this->company->getId())?->getName());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function invalidCompanyNames(): iterable
    {
        yield 'null' => [null];
        yield 'blank' => ['   '];
        yield 'too short' => ['ab'];
        yield 'too long' => [str_repeat('a', 46)];
    }
}
