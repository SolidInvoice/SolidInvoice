<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2020
 */

namespace SolidInvoice\UserBundle\Repository;

use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @method findOneBy(array $criteria = [])
 */
interface UserRepositoryInterface extends UserProviderInterface, UserLoaderInterface
{
}
