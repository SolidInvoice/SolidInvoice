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

namespace SolidInvoice\EInvoiceBundle\Tests\Validation;

use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Enum\ValidationStage;
use SolidInvoice\EInvoiceBundle\Profile\ProfileInterface;
use SolidInvoice\EInvoiceBundle\Validation\ValidatorInterface;
use SolidInvoice\EInvoiceBundle\Validation\ValidatorRegistry;

final class ValidatorRegistryTest extends TestCase
{
    public function testSupportingOnEmptyRegistryReturnsEmptyArray(): void
    {
        $registry = new ValidatorRegistry([]);

        self::assertSame([], $registry->supporting($this->profile(), ValidationStage::Schema));
    }

    public function testSupportingReturnsOnlyMatchingValidatorsInRegistrationOrder(): void
    {
        $profile = $this->profile();
        $stage = ValidationStage::Business;

        $unsupported = $this->validator($profile, $stage, false);
        $first = $this->validator($profile, $stage, true);
        $second = $this->validator($profile, $stage, true);

        $registry = new ValidatorRegistry([$unsupported, $first, $second]);

        self::assertSame([$first, $second], $registry->supporting($profile, $stage));
    }

    public function testAllPreservesRegistrationOrder(): void
    {
        $first = $this->validator($this->profile(), ValidationStage::Schema, true);
        $second = $this->validator($this->profile(), ValidationStage::Schema, true);

        $registry = new ValidatorRegistry([$first, $second]);

        self::assertSame([$first, $second], $registry->all());
    }

    private function profile(): ProfileInterface
    {
        return $this->createStub(ProfileInterface::class);
    }

    private function validator(ProfileInterface $profile, ValidationStage $stage, bool $supports): ValidatorInterface
    {
        $validator = $this->createStub(ValidatorInterface::class);
        $validator->method('supports')->willReturnCallback(
            static fn (ProfileInterface $givenProfile, ValidationStage $givenStage): bool => $supports && $givenProfile === $profile && $givenStage === $stage,
        );

        return $validator;
    }
}
