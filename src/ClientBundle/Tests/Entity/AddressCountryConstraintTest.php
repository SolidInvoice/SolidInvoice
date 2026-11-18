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

namespace SolidInvoice\ClientBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Address;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddressCountryConstraintTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validCountryProvider(): iterable
    {
        yield 'ZA' => ['ZA'];
        yield 'null' => [null];
    }

    #[DataProvider('validCountryProvider')]
    public function testAcceptsAValidCountry(?string $country): void
    {
        $violations = $this->validator->validatePropertyValue(Address::class, 'country', $country);

        self::assertCount(0, $violations);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCountryProvider(): iterable
    {
        yield 'unknown two-letter code' => ['XX'];
        yield 'three-letter code' => ['ZAF'];
        yield 'lowercase' => ['za'];
    }

    #[DataProvider('invalidCountryProvider')]
    public function testRejectsAnInvalidCountry(string $country): void
    {
        $violations = $this->validator->validatePropertyValue(Address::class, 'country', $country);

        self::assertCount(1, $violations);
    }
}
