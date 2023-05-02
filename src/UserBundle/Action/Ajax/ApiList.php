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

namespace SolidInvoice\UserBundle\Action\Ajax;

use SolidInvoice\CoreBundle\Traits\SerializeTrait;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiList
{
    use SerializeTrait;

    public function __construct(private readonly ApiTokenRepository $repository, private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function __invoke(Request $request)
    {
        $token = $this->tokenStorage->getToken();
        $user = $token instanceof TokenInterface ? $token->getUser() : null;

        if ($user instanceof UserInterface) {
            $tokens = $this->repository->getApiTokensForUser($user);

            return $this->serialize($tokens);
        }

        return $this->serialize([]);
    }
}
