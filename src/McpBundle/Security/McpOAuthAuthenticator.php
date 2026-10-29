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
use SensitiveParameter;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\OAuth\ServerFactoryInterface;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class McpOAuthAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public const string ATTR_ACCESS_TOKEN_ID = 'mcp_oauth_access_token_id';

    public const string ATTR_ACCESS_TOKEN = 'mcp_oauth_access_token';

    public const string ATTR_SCOPES = 'mcp_oauth_scopes';

    public const string ATTR_COMPANY_ID = 'mcp_oauth_company_id';

    private readonly PsrHttpFactory $psrHttpFactory;

    public function __construct(
        private readonly ServerFactoryInterface $serverFactory,
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly CompanySelector $companySelector,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly UrlGeneratorInterface $urlGenerator,
        Psr17Factory $psr17Factory = new Psr17Factory(),
    ) {
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        try {
            $validatedRequest = $this->serverFactory
                ->createResourceServer()
                ->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $exception) {
            throw new CustomUserMessageAuthenticationException($exception->getMessage(), [], $exception->getCode(), $exception);
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

        if (! $token->getUser()->getCompanies()->contains($token->getCompany())) {
            throw new CustomUserMessageAuthenticationException('User no longer has access to the company this token was issued for.');
        }

        $request->attributes->set(self::ATTR_ACCESS_TOKEN_ID, $jti);
        $request->attributes->set(self::ATTR_ACCESS_TOKEN, $token);
        $request->attributes->set(self::ATTR_SCOPES, \is_array($scopes) ? $scopes : []);
        $request->attributes->set(self::ATTR_COMPANY_ID, $token->getCompany()->getId()->toRfc4122());

        return new SelfValidatingPassport(new UserBadge($userId));
    }

    public function onAuthenticationSuccess(Request $request, #[SensitiveParameter] TokenInterface $token, string $firewallName): ?Response
    {
        $accessToken = $request->attributes->get(self::ATTR_ACCESS_TOKEN);

        if (! $accessToken instanceof McpAccessToken) {
            return null;
        }

        $companyId = $accessToken->getCompany()
            ->getId();

        $this->companySelector->switchCompany($companyId);

        $this->accessTokenRepository->touch($accessToken);

        $decision = new AccessDecision();

        if (! $this->authorizationChecker->isGranted(Attribute::ACCESS, null, $decision)) {
            return $this->buildAccessDeniedResponse($this->extractReason($decision));
        }

        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return $this->buildErrorResponse('invalid_token', $exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    // No bearer token at all: point the client at the RFC 9728 metadata so it can discover how to authenticate.
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            [
                'error' => 'invalid_request',
                'error_description' => 'Missing bearer token.',
            ],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => sprintf('Bearer resource_metadata="%s"', $this->resourceMetadataUrl())],
        );
    }

    private function buildAccessDeniedResponse(string $reason): Response
    {
        return $this->buildErrorResponse('access_denied', $reason, Response::HTTP_FORBIDDEN);
    }

    private function buildErrorResponse(string $error, string $message, int $statusCode): JsonResponse
    {
        // Strip CR/LF and double-quotes before interpolating into the
        // WWW-Authenticate header so upstream error text can't break the
        // header or inject additional fields.
        $headerSafeMessage = preg_replace('/[\r\n]+/', ' ', str_replace('"', "'", $message)) ?? '';

        return new JsonResponse(
            [
                'error' => $error,
                'error_description' => $message,
            ],
            $statusCode,
            [
                'WWW-Authenticate' => sprintf('Bearer resource_metadata="%s", error="%s", error_description="%s"', $this->resourceMetadataUrl(), $error, $headerSafeMessage),
            ],
        );
    }

    private function resourceMetadataUrl(): string
    {
        return $this->urlGenerator->generate('mcp_well_known_protected_resource_mcp', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function extractReason(AccessDecision $decision): string
    {
        $reasons = [];

        foreach ($decision->votes as $vote) {
            foreach ($vote->reasons as $reason) {
                $reasons[] = $reason;
            }
        }

        $message = trim(implode(' ', $reasons));

        return $message === '' ? 'Access denied.' : $message;
    }
}
