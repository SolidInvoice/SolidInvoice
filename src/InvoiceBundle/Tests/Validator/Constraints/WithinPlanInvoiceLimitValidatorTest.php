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

namespace SolidInvoice\InvoiceBundle\Tests\Validator\Constraints;

use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Psr\Clock\ClockInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Validator\Constraints\WithinPlanInvoiceLimit;
use SolidInvoice\InvoiceBundle\Validator\Constraints\WithinPlanInvoiceLimitValidator;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<WithinPlanInvoiceLimitValidator>
 */
#[CoversClass(WithinPlanInvoiceLimit::class)]
#[CoversClass(WithinPlanInvoiceLimitValidator::class)]
final class WithinPlanInvoiceLimitValidatorTest extends ConstraintValidatorTestCase
{
    private Stub & InvoiceRepository $invoiceRepository;

    private MockObject & FeatureGate $featureGate;

    private MockObject & ToggleInterface $toggle;

    private Stub & EntityManagerInterface $entityManager;

    protected function createValidator(): WithinPlanInvoiceLimitValidator
    {
        $this->invoiceRepository = $this->createStub(InvoiceRepository::class);
        $this->featureGate = $this->createMock(FeatureGate::class);
        $this->toggle = $this->createMock(ToggleInterface::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(CarbonImmutable::parse('2026-06-15'));

        return new WithinPlanInvoiceLimitValidator($this->invoiceRepository, $this->featureGate, $clock, $this->toggle, $this->entityManager);
    }

    public function testSkipsExistingInvoice(): void
    {
        // A managed (already-persisted) entity is an edit, not a create.
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('saas_enabled')
            ->willReturn(true);
        $this->entityManager->method('contains')->willReturn(true);
        $this->featureGate->expects($this->never())->method('canUse');

        $this->validator->validate(new Invoice(), new WithinPlanInvoiceLimit());

        $this->assertNoViolation();
    }

    public function testSkipsWhenNotSaas(): void
    {
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('saas_enabled')
            ->willReturn(false);
        $this->featureGate->expects($this->never())->method('canUse');

        $this->validator->validate(new Invoice(), new WithinPlanInvoiceLimit());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenWithinLimit(): void
    {
        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('saas_enabled')
            ->willReturn(true);
        $this->entityManager->method('contains')->willReturn(false);
        $this->invoiceRepository->method('countCreatedInMonth')->willReturn(2);
        $this->featureGate->expects($this->once())
            ->method('canUse')
            ->with(Feature::InvoicesPerMonth->value, 2)
            ->willReturn(true);

        $this->validator->validate(new Invoice(), new WithinPlanInvoiceLimit());

        $this->assertNoViolation();
    }

    public function testViolationWhenLimitReached(): void
    {
        $constraint = new WithinPlanInvoiceLimit();

        $this->toggle
            ->expects($this->once())
            ->method('isActive')
            ->with('saas_enabled')
            ->willReturn(true);
        $this->entityManager->method('contains')->willReturn(false);
        $this->invoiceRepository->method('countCreatedInMonth')->willReturn(20);
        $this->featureGate->expects($this->once())
            ->method('canUse')
            ->with(Feature::InvoicesPerMonth->value, 20)
            ->willReturn(false);

        $this->validator->validate(new Invoice(), $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongConstraintTypeThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Invoice(), $this->createStub(Constraint::class));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongValueTypeThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-an-invoice', new WithinPlanInvoiceLimit());
    }
}
