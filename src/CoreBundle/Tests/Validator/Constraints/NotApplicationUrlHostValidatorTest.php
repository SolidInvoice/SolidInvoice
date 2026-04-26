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

namespace SolidInvoice\CoreBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\Attributes\DataProvider;
use SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHost;
use SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHostValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @covers \SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHost
 * @covers \SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHostValidator
 *
 * @extends ConstraintValidatorTestCase<NotApplicationUrlHostValidator>
 */
final class NotApplicationUrlHostValidatorTest extends ConstraintValidatorTestCase
{
    private string $applicationUrl = 'https://app.example.com';

    protected function createValidator(): NotApplicationUrlHostValidator
    {
        return new NotApplicationUrlHostValidator($this->applicationUrl);
    }

    public function testNullValuePasses(): void
    {
        $this->validator->validate(null, new NotApplicationUrlHost());
        $this->assertNoViolation();
    }

    public function testEmptyStringPasses(): void
    {
        $this->validator->validate('', new NotApplicationUrlHost());
        $this->assertNoViolation();
    }

    public function testCustomDomainPasses(): void
    {
        $this->validator->validate('acme.example', new NotApplicationUrlHost());
        $this->assertNoViolation();
    }

    public function testEqualToApplicationHostFails(): void
    {
        $constraint = new NotApplicationUrlHost();

        $this->validator->validate('APP.example.com', $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    #[DataProvider('provideUrlAndPortPrefixedInputs')]
    public function testUrlAndPortPrefixedInputsFail(string $input): void
    {
        $constraint = new NotApplicationUrlHost();

        $this->validator->validate($input, $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideUrlAndPortPrefixedInputs(): iterable
    {
        yield 'scheme prefix' => ['https://app.example.com'];
        yield 'port suffix' => ['app.example.com:8080'];
        yield 'scheme and path' => ['https://app.example.com/some/path'];
        yield 'trailing dot' => ['app.example.com.'];
    }

    public function testEmptyApplicationUrlSkipsValidation(): void
    {
        $this->applicationUrl = '';
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate('app.example.com', new NotApplicationUrlHost());
        $this->assertNoViolation();
    }
}
