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

namespace SolidInvoice\ClientBundle\Tests\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Validator\Constraints\WithinPlanClientLimit;
use SolidInvoice\ClientBundle\Validator\Constraints\WithinPlanClientLimitValidator;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<WithinPlanClientLimitValidator>
 */
#[CoversClass(WithinPlanClientLimit::class)]
#[CoversClass(WithinPlanClientLimitValidator::class)]
final class WithinPlanClientLimitValidatorTest extends ConstraintValidatorTestCase
{
    private Stub&ClientRepository $clientRepository;

    private MockObject&FeatureGate $featureGate;

    private ModeResolver $modeResolver;

    private Stub&EntityManagerInterface $entityManager;

    protected function createValidator(): WithinPlanClientLimitValidator
    {
        $this->clientRepository = $this->createStub(ClientRepository::class);
        $this->featureGate = $this->createMock(FeatureGate::class);
        $this->modeResolver = new ModeResolver('saas');
        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        return new WithinPlanClientLimitValidator($this->clientRepository, $this->featureGate, $this->modeResolver, $this->entityManager);
    }

    public function testSkipsExistingClient(): void
    {
        // A managed (already-persisted) entity is an edit, not a create.
        $this->entityManager->method('contains')->willReturn(true);
        $this->featureGate->expects($this->never())->method('canUse');

        $this->validator->validate(new Client(), new WithinPlanClientLimit());

        $this->assertNoViolation();
    }

    public function testSkipsWhenNotSaas(): void
    {
        $this->modeResolver = new ModeResolver();
        $this->validator = new WithinPlanClientLimitValidator($this->clientRepository, $this->featureGate, $this->modeResolver, $this->entityManager);
        $this->validator->initialize($this->context);
        $this->featureGate->expects($this->never())->method('canUse');

        $this->validator->validate(new Client(), new WithinPlanClientLimit());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenWithinLimit(): void
    {
        $this->entityManager->method('contains')->willReturn(false);
        $this->clientRepository->method('getTotalClients')->willReturn(3);
        $this->featureGate->expects($this->once())
            ->method('canUse')
            ->with(Feature::TotalClients->value, 3)
            ->willReturn(true);

        $this->validator->validate(new Client(), new WithinPlanClientLimit());

        $this->assertNoViolation();
    }

    public function testViolationWhenLimitReached(): void
    {
        $constraint = new WithinPlanClientLimit();

        $this->entityManager->method('contains')->willReturn(false);
        $this->clientRepository->method('getTotalClients')->willReturn(10);
        $this->featureGate
            ->expects($this->once())
            ->method('canUse')
            ->with(Feature::TotalClients->value, 10)
            ->willReturn(false);

        $this->validator->validate(new Client(), $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongConstraintTypeThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new Client(), $this->createStub(Constraint::class));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongValueTypeThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-client', new WithinPlanClientLimit());
    }
}
