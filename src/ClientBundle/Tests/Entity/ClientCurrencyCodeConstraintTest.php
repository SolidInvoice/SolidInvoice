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
use SolidInvoice\ClientBundle\Entity\Client;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ClientCurrencyCodeConstraintTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCurrencyCodeProvider(): iterable
    {
        yield 'USD' => ['USD'];
        yield 'KWD' => ['KWD'];
    }

    #[DataProvider('validCurrencyCodeProvider')]
    public function testAcceptsAValidIsoCurrencyCode(string $currencyCode): void
    {
        $violations = $this->validator->validatePropertyValue(Client::class, 'currencyCode', $currencyCode);

        self::assertCount(0, $violations);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidCurrencyCodeProvider(): iterable
    {
        yield 'FOO' => ['FOO'];
        yield '123' => ['123'];
    }

    #[DataProvider('invalidCurrencyCodeProvider')]
    public function testRejectsAnInvalidCurrencyCode(string $currencyCode): void
    {
        $violations = $this->validator->validatePropertyValue(Client::class, 'currencyCode', $currencyCode);

        self::assertCount(1, $violations);
    }
}
