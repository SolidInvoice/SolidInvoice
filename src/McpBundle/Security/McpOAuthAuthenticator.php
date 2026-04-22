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

namespace SolidInvoice\McpBundle\Security;

use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\OAuth\ServerFactory;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class McpOAuthAuthenticator extends AbstractAuthenticator
{
    public const string ATTR_ACCESS_TOKEN_ID = 'mcp_oauth_access_token_id';

    public const string ATTR_SCOPES = 'mcp_oauth_scopes';

    public const string ATTR_COMPANY_ID = 'mcp_oauth_company_id';

    public function __construct(
        private readonly ServerFactory $serverFactory,
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly CompanySelector $companySelector,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $psr17 = new Psr17Factory();
        $factory = new PsrHttpFactory($psr17, $psr17, $psr17, $psr17);
        $psrRequest = $factory->createRequest($request);

        try {
            $validatedRequest = $this->serverFactory
                ->createResourceServer()
                ->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage());
        }

        $jti = $validatedRequest->getAttribute('oauth_access_token_id');
        $userId = $validatedRequest->getAttribute('oauth_user_id');
        $scopes = $validatedRequest->getAttribute('oauth_scopes');

        if (! \is_string($jti) || $jti === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token: missing identifier.');
        }

        if (! \is_string($userId) || $userId === '') {
            throw new CustomUserMessageAuthenticationException('Invalid access token: missing user.');
        }

        $token = $this->accessTokenRepository->findByJti($jti);

        if (! $token instanceof McpAccessToken || $token->isRevoked()) {
            throw new CustomUserMessageAuthenticationException('Access token revoked or not found.');
        }

        $request->attributes->set(self::ATTR_ACCESS_TOKEN_ID, $jti);
        $request->attributes->set(self::ATTR_SCOPES, \is_array($scopes) ? $scopes : []);
        $request->attributes->set(self::ATTR_COMPANY_ID, $token->getCompany()->getId()?->toRfc4122());

        return new SelfValidatingPassport(new UserBadge($userId));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $jti = $request->attributes->get(self::ATTR_ACCESS_TOKEN_ID);

        if (! \is_string($jti)) {
            return null;
        }

        $accessToken = $this->accessTokenRepository->findByJti($jti);

        if ($accessToken instanceof McpAccessToken) {
            $companyId = $accessToken->getCompany()->getId();

            if ($companyId !== null) {
                $this->companySelector->switchCompany($companyId);
            }
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            [
                'error' => 'invalid_token',
                'error_description' => $exception->getMessage(),
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'WWW-Authenticate' => sprintf('Bearer error="invalid_token", error_description="%s"', addslashes($exception->getMessage())),
            ],
        );
    }
}
