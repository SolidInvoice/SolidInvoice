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

namespace SolidInvoice\UserBundle\Tests\DTO;

use SolidInvoice\CoreBundle\Test\Traits\FakerTestTrait;
use SolidInvoice\UserBundle\DTO\ChangePassword;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ChangePasswordTest extends KernelTestCase
{
    use FakerTestTrait;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidPasswordChange(): void
    {
        $dto = new ChangePassword();
        $dto->plainPassword = 'NewSecureP@ssw0rd2024!';

        // Validate only the plainPassword field to avoid UserPassword constraint
        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        // Should pass length, strength, and not-compromised checks
        self::assertCount(0, $violations);
    }

    public function testPasswordTooShort(): void
    {
        $dto = new ChangePassword();
        $dto->plainPassword = 'Short1!';

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        $passwordViolations = [];
        foreach ($violations as $violation) {
            $passwordViolations[] = $violation->getMessage();
        }

        self::assertContains('Your password must be at least 8 characters long', $passwordViolations);
    }

    public function testPasswordTooWeak(): void
    {
        $dto = new ChangePassword();
        $dto->plainPassword = 'password';

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        $passwordViolations = [];
        foreach ($violations as $violation) {
            $passwordViolations[] = $violation->getMessage();
        }

        self::assertContains('Your password is too weak. Please use a stronger password with a mix of letters, numbers, and symbols.', $passwordViolations);
    }

    public function testPasswordCannotBeBlank(): void
    {
        $dto = new ChangePassword();
        $dto->plainPassword = '';

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        $hasBlankError = false;
        foreach ($violations as $violation) {
            if (str_contains((string) $violation->getMessage(), 'Please enter a password')) {
                $hasBlankError = true;
                break;
            }
        }

        self::assertTrue($hasBlankError);
    }

    public function testCurrentPasswordCannotBeBlank(): void
    {
        $dto = new ChangePassword();
        $dto->currentPassword = '';

        $violations = $this->validator->validateProperty($dto, 'currentPassword');

        self::assertGreaterThan(0, $violations->count());
    }

    public function testPasswordLengthMaximum(): void
    {
        $dto = new ChangePassword();
        // Create a password longer than 4096 characters
        $dto->plainPassword = str_repeat('a', 4097);

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        self::assertGreaterThan(0, $violations->count());
    }

    public function testPasswordMeetsAllRequirements(): void
    {
        $dto = new ChangePassword();
        $dto->plainPassword = 'SecureP@ssw0rd2024!';

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        // Should pass length, strength, and not-compromised checks
        self::assertCount(0, $violations);
    }

    public function testCommonPasswordsAreRejected(): void
    {
        $dto = new ChangePassword();
        // "password" is a very commonly compromised password
        $dto->plainPassword = 'password';

        $violations = $this->validator->validateProperty($dto, 'plainPassword');

        $hasCompromisedOrWeakError = false;
        foreach ($violations as $violation) {
            $message = (string) $violation->getMessage();
            if (str_contains($message, 'leaked in a data breach') || str_contains($message, 'too weak')) {
                $hasCompromisedOrWeakError = true;
                break;
            }
        }

        // Should fail either the NotCompromisedPassword or PasswordStrength constraint
        self::assertTrue($hasCompromisedOrWeakError);
    }
}
