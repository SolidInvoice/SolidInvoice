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

namespace SolidInvoice\CoreBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function str_repeat;

final class CompanyNameLengthTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testNameWithinLimitIsValid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', str_repeat('a', 45));

        self::assertCount(0, $violations);
    }

    public function testNameOverLimitIsInvalid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', str_repeat('a', 46));

        self::assertCount(1, $violations);
        self::assertSame('core.company.name.max_length', $violations->get(0)->getMessageTemplate());
    }

    public function testNameUnderMinimumIsInvalid(): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', 'ab');

        self::assertCount(1, $violations);
        self::assertSame('core.company.name.min_length', $violations->get(0)->getMessageTemplate());
    }

    #[DataProvider('rejectedNames')]
    public function testSpamNameIsInvalid(string $name, string $message): void
    {
        $violations = $this->validator->validatePropertyValue(Company::class, 'name', $name);

        self::assertCount(1, $violations);
        self::assertSame($message, $violations->get(0)->getMessageTemplate());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejectedNames(): iterable
    {
        yield 'phone number' => ['Call to cancel +1 408 634 3062', 'core.company.name.too_many_digits'];
        yield 'compact phone number' => ['Call 0821234567', 'core.company.name.too_many_digits'];
        yield 'http link' => ['Visit https://x.co', 'core.company.name.no_url'];
        yield 'www link' => ['See www.scam', 'core.company.name.no_url'];
        yield 'bare domain' => ['Go to scam-site.com now', 'core.company.name.no_url'];
    }

    #[DataProvider('acceptedNames')]
    public function testLegitimateNameIsValid(string $name): void
    {
        self::assertCount(0, $this->validator->validatePropertyValue(Company::class, 'name', $name));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedNames(): iterable
    {
        yield 'year' => ['Studio 2024'];
        yield 'five digits' => ['Unit 12345 Holdings'];
        yield 'trailing abbreviation' => ['Acme Co.'];
        yield 'initials' => ['J.R. Smith & Sons'];
        yield 'brackets' => ['Acme (Pty) Ltd'];
    }
}
