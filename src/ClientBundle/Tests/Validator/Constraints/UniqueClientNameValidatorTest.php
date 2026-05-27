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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\ClientBundle\Validator\Constraints\UniqueClientName;
use SolidInvoice\ClientBundle\Validator\Constraints\UniqueClientNameValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UniqueClientNameValidator>
 */
#[CoversClass(UniqueClientName::class)]
#[CoversClass(UniqueClientNameValidator::class)]
final class UniqueClientNameValidatorTest extends ConstraintValidatorTestCase
{
    private MockObject&ClientRepository $clientRepository;

    protected function createValidator(): UniqueClientNameValidator
    {
        $this->clientRepository = $this->createMock(ClientRepository::class);

        return new UniqueClientNameValidator($this->clientRepository);
    }

    public function testNullValuePassesValidation(): void
    {
        $this->clientRepository->expects($this->never())->method('findOneByNameIncludingArchived');

        $this->validator->validate(null, new UniqueClientName());

        $this->assertNoViolation();
    }

    public function testEmptyStringPassesValidation(): void
    {
        $this->clientRepository->expects($this->never())->method('findOneByNameIncludingArchived');

        $this->validator->validate('', new UniqueClientName());

        $this->assertNoViolation();
    }

    public function testClientNameNotInDatabasePassesValidation(): void
    {
        $this->clientRepository
            ->expects($this->once())
            ->method('findOneByNameIncludingArchived')
            ->with('Acme Corp')
            ->willReturn(null);

        $this->validator->validate('Acme Corp', new UniqueClientName());

        $this->assertNoViolation();
    }

    public function testDuplicateClientNameAddsViolation(): void
    {
        $constraint = new UniqueClientName();

        $this->clientRepository
            ->expects($this->once())
            ->method('findOneByNameIncludingArchived')
            ->with('Existing Client')
            ->willReturn(new Client());

        $this->validator->validate('Existing Client', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', 'Existing Client')
            ->assertRaised();
    }

    public function testWrongConstraintTypeThrows(): void
    {
        $this->clientRepository->expects($this->never())->method('findOneByNameIncludingArchived');
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('foo', $this->createStub(Constraint::class));
    }

    public function testNonStringValueThrows(): void
    {
        $this->clientRepository->expects($this->never())->method('findOneByNameIncludingArchived');
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(42, new UniqueClientName());
    }
}
