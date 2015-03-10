<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\ApiBundle\Security;

use CSBill\ApiBundle\Security\Provider\ApiTokenUserProvider;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class ApiTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{
    /**
     * @var ApiTokenUserProvider
     */
    protected $userProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ApiTokenUserProvider $userProvider
     * @param SerializerInterface  $serializer
     */
    public function __construct(ApiTokenUserProvider $userProvider, SerializerInterface $serializer)
    {
        $this->userProvider = $userProvider;
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param string  $providerKey
     *
     * @return PreAuthenticatedToken
     */
    public function createToken(Request $request, $providerKey)
    {
        $token = $request->headers->get('token', $request->query->get('token'));

        if (!$token) {
            throw new BadCredentialsException('No API token found');
            // skip api key authentication when we allow other methods of authentication against the api
            // return null;
        }

        return new PreAuthenticatedToken(
            'anon.',
            $token,
            $providerKey
        );
    }

    /**
     * @param TokenInterface        $token
     * @param UserProviderInterface $userProvider
     * @param string                $providerKey
     *
     * @return PreAuthenticatedToken
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        /** @var ApiTokenUserProvider $userProvider */

        $apiToken = $token->getCredentials();
        $username = $userProvider->getUsernameForToken($apiToken);

        if (!$username) {
            throw new AuthenticationException(sprintf('API Token \'%s\' is invalid.', $apiToken));
        }

        $user = $userProvider->loadUserByUsername($username);

        return new PreAuthenticatedToken(
            $user,
            $apiToken,
            $providerKey,
            $user->getRoles()
        );
    }

    /**
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return bool
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $response = array('message' => $exception->getMessage());

        $content = $this->serializer->serialize($response, $request->getRequestFormat());

        return new Response($content, 403);
    }
}
