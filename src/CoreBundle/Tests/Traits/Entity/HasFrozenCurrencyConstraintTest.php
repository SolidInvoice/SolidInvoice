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

namespace SolidInvoice\CoreBundle\Tests\Traits\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Covers the `#[Assert\Currency]` constraint {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency}
 * maps onto `currencyCode`, on both entities that use the trait.
 */
final class HasFrozenCurrencyConstraintTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function validCurrencyCodeProvider(): iterable
    {
        foreach ([Invoice::class, Quote::class] as $entityClass) {
            yield $entityClass . ' USD' => [$entityClass, 'USD'];
            yield $entityClass . ' KWD' => [$entityClass, 'KWD'];
        }
    }

    /**
     * @param class-string $entityClass
     */
    #[DataProvider('validCurrencyCodeProvider')]
    public function testAcceptsAValidIsoCurrencyCode(string $entityClass, string $currencyCode): void
    {
        $violations = $this->validator->validatePropertyValue($entityClass, 'currencyCode', $currencyCode);

        self::assertCount(0, $violations);
    }

    /**
     * @return iterable<string, array{class-string, string}>
     */
    public static function invalidCurrencyCodeProvider(): iterable
    {
        foreach ([Invoice::class, Quote::class] as $entityClass) {
            yield $entityClass . ' FOO' => [$entityClass, 'FOO'];
            yield $entityClass . ' 123' => [$entityClass, '123'];
        }
    }

    /**
     * @param class-string $entityClass
     */
    #[DataProvider('invalidCurrencyCodeProvider')]
    public function testRejectsAnInvalidCurrencyCode(string $entityClass, string $currencyCode): void
    {
        $violations = $this->validator->validatePropertyValue($entityClass, 'currencyCode', $currencyCode);

        self::assertCount(1, $violations);
    }
}
