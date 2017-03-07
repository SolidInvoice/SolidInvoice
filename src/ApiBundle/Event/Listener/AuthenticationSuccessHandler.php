<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Event\Listener;

use CSBill\ApiBundle\ApiTokenManager;
use CSBill\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var ApiTokenManager
     */
    private $tokenManager;

    /**
     * AuthenticationSuccessHandler constructor.
     *
     * @param ApiTokenManager $tokenManager
     */
    public function __construct(ApiTokenManager $tokenManager)
    {
	$this->tokenManager = $tokenManager;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
	/** @var User $user */
	$user = $token->getUser();

	$token = $this->tokenManager->getOrCreate($user, $request->request->get('token_name') ?: 'API Token');

	$response = new JsonResponse(['token' => $token->getToken()]);

	return $response;
    }
}
