<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Step;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class CreateUserStep implements InstallationStepInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public static function priority(): int
    {
        return 5;
    }

    public function execute(Installation $installationData, ?callable $callback = null): \Generator
    {
        $user = new User();

        $encoder = $this->passwordHasherFactory->getPasswordHasher($user);

        $password = $encoder->hash($installationData->userAccount->password);

        $user->setEmail($installationData->userAccount->emailAddress)
            ->setFirstName($installationData->userAccount->firstName)
            ->setLastName($installationData->userAccount->lastName)
            ->setPassword($password)
            ->setVerified(true)
            ->setEnabled(true);

        try {
            $this->userRepository->save($user);

            if ($callback) {
                yield from $callback('Admin user created');
            }
        } catch (UniqueConstraintViolationException) {
            if ($callback) {
                yield from $callback('Admin user already exists, skipping creation');
            }
        }
    }

    public static function getLabel(): string
    {
        return 'Creating admin user';
    }
}
