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

namespace SolidInvoice\UserBundle\Registration;

use SensitiveParameter;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\SignupMethod;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creates a password account. Callers validate the input first (see DTO\Registration).
 */
final readonly class UserRegistrar
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    public function register(string $email, #[SensitiveParameter] string $plainPassword, SignupMethod $signupMethod, ?Company $company = null): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setSignupMethod($signupMethod);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setEnabled(true);

        if ($company instanceof Company) {
            $user->addCompany($company);
        }

        $this->userRepository->save($user);

        return $user;
    }
}
