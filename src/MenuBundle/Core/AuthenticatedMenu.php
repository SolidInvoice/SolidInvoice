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

namespace SolidInvoice\MenuBundle\Core;

use SolidInvoice\MenuBundle\Builder\BuilderInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

/**
 * @see \SolidInvoice\MenuBundle\Tests\Core\AuthenticatedMenuTest
 */
class AuthenticatedMenu implements ContainerAwareInterface, BuilderInterface
{
    use ContainerAwareTrait;

    public function validate(): bool
    {
        try {
            $security = $this->container->get('security.authorization_checker');

            return $security->isGranted('IS_AUTHENTICATED_REMEMBERED');
        } catch (AuthenticationCredentialsNotFoundException $e) {
            return false;
        }
    }
}
