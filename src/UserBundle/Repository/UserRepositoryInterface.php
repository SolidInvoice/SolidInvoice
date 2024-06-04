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

namespace SolidInvoice\UserBundle\Repository;

use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @method findOneBy(array $criteria = [])
 * @method UserInterface|null find(int $id, int|null $lockMode = null, string|null $lockVersion = null)
 * @method save(UserInterface $user)
 *
 * @extends UserProviderInterface<User>
 */
interface UserRepositoryInterface extends UserProviderInterface, UserLoaderInterface
{
    public function getUserCount(): int;
}
