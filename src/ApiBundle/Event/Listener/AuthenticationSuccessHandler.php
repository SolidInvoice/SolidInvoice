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

namespace SolidInvoice\ApiBundle\Event\Listener;

use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly ApiTokenManager $tokenManager,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();

        $name = $request->request->get('token_name') ?: 'API Token';

        // Tokens are stored as hashes, so we cannot return the plaintext of an
        // existing token. Refuse to silently invalidate one — the caller must
        // pick a unique name or revoke the existing token first.
        foreach ($user->getApiTokens() as $existing) {
            if ($existing->getName() === $name) {
                return new JsonResponse(
                    [
                        'error' => 'token_name_already_exists',
                        'message' => sprintf(
                            'An API token named "%s" already exists. Revoke it first or pass a different "token_name" parameter.',
                            $name,
                        ),
                    ],
                    Response::HTTP_CONFLICT,
                );
            }
        }

        $generated = $this->tokenManager->create($user, $name);

        return new JsonResponse(['token' => $generated->plaintext]);
    }
}
