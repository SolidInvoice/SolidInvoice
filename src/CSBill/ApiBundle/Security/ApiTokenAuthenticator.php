<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Security;

use CSBill\ApiBundle\Security\Provider\ApiTokenUserProvider;
use CSBill\UserBundle\Entity\ApiTokenHistory;
use CSBill\UserBundle\Repository\ApiTokenHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
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
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class ApiTokenAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface, AuthenticationSuccessHandlerInterface
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
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ApiTokenUserProvider   $userProvider
     * @param SerializerInterface    $serializer
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ApiTokenUserProvider $userProvider,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager
    ) {
        $this->userProvider = $userProvider;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    /**
     * @param Request $request
     * @param string  $providerKey
     *
     * @return PreAuthenticatedToken
     */
    public function createToken(Request $request, $providerKey)
    {
        $token = $this->getToken($request);

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
        /* @var ApiTokenUserProvider $userProvider */

        $apiToken = $token->getCredentials();
        $username = $userProvider->getUsernameForToken($apiToken);

        if (!$username) {
            throw new AuthenticationException(sprintf('API Token \'%s\' is invalid.', $apiToken));
        }

        $user = $userProvider->loadUserByUsername($username);

        $roles = array_merge(
            $user->getRoles(),
            array(
                'ROLE_API_AUTHENTICATED',
            )
        );

        return new PreAuthenticatedToken($user, $apiToken, $providerKey, $roles);
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

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $apiToken = $this->getToken($request);

        $history = new ApiTokenHistory();

        $history->setMethod($request->getMethod())
            ->setIp($request->getClientIp())
            ->setRequestData($request->request->all())
            ->setUserAgent($request->server->get('HTTP_USER_AGENT'))
            ->setResource($request->getPathInfo())
        ;

        /** @var ApiTokenHistoryRepository $repository */
        $repository = $this->entityManager->getRepository('CSBillUserBundle:ApiTokenHistory');

        $repository->addHistory($history, $apiToken);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function getToken(Request $request)
    {
        return $request->headers->get('token', $request->query->get('token'));
    }
}
