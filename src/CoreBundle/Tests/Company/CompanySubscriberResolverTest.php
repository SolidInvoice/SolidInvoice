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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\CoreBundle\Company\CompanySubscriberResolver;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Component\Uid\Ulid;

#[CoversClass(CompanySubscriberResolver::class)]
final class CompanySubscriberResolverTest extends TestCase
{
    public function testReturnsNullWhenNoCompanyInContext(): void
    {
        $selector = $this->createStub(CompanySelectorInterface::class);
        $selector->method('getCompany')->willReturn(null);

        $repository = $this->createMock(CompanyRepository::class);
        $repository->expects(self::never())->method('find');

        $resolver = new CompanySubscriberResolver($selector, $repository);

        self::assertNull($resolver->resolve());
    }

    public function testReturnsCompanyWhenSelectorHasUlid(): void
    {
        $id = new Ulid();
        $company = new Company();

        $selector = $this->createStub(CompanySelectorInterface::class);
        $selector->method('getCompany')->willReturn($id);

        $repository = $this->createMock(CompanyRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with($id)
            ->willReturn($company);

        $resolver = new CompanySubscriberResolver($selector, $repository);

        self::assertSame($company, $resolver->resolve());
    }

    public function testReturnsNullWhenRepositoryCannotFindCompany(): void
    {
        $id = new Ulid();

        $selector = $this->createStub(CompanySelectorInterface::class);
        $selector->method('getCompany')->willReturn($id);

        $repository = $this->createMock(CompanyRepository::class);
        $repository->expects(self::once())->method('find')->with($id)->willReturn(null);

        $resolver = new CompanySubscriberResolver($selector, $repository);

        self::assertNull($resolver->resolve());
    }
}
