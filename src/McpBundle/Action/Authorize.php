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

namespace SolidInvoice\McpBundle\Action;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Company\UserEligibleCompanies;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\OAuth\ConsentService;
use SolidInvoice\McpBundle\OAuth\OAuthUserEntity;
use SolidInvoice\McpBundle\OAuth\PendingAuthorization;
use SolidInvoice\McpBundle\OAuth\ScopeEntity;
use SolidInvoice\McpBundle\OAuth\ServerFactoryInterface;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

#[Route(path: '/oauth/authorize', name: 'mcp_oauth_authorize', methods: ['GET', 'POST'])]
final class Authorize
{
    private const string CONSENT_CSRF_TOKEN_ID = 'mcp_oauth_consent';

    private readonly PsrHttpFactory $psrHttpFactory;

    public function __construct(
        private readonly ServerFactoryInterface $serverFactory,
        private readonly ConsentService $consentService,
        private readonly PendingAuthorization $pendingAuthorization,
        private readonly Security $security,
        private readonly CompanySelector $companySelector,
        private readonly UserEligibleCompanies $eligibleCompanies,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Psr17Factory $psr17Factory = new Psr17Factory(),
    ) {
        $this->psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            // Save the current request URL so Symfony Security can redirect
            // back here after login. SelectCompany then picks it up on the way
            // to the dashboard.
            $request->getSession()->set('_security.main.target_path', $request->getUri());

            return new RedirectResponse($this->urlGenerator->generate('_login_main'));
        }

        $server = $this->serverFactory->createAuthorizationServer();

        $psrRequest = $this->toPsrRequest($request);

        try {
            $authRequest = $server->validateAuthorizationRequest($psrRequest);
        } catch (OAuthServerException $exception) {
            $this->logger->notice('OAuth authorize request rejected', [
                'reason' => $exception->getMessage(),
                'code' => $exception->getErrorType(),
            ]);

            return $this->renderError($exception->getErrorType(), $exception->getMessage(), $exception->getHttpStatusCode());
        }

        $client = $authRequest->getClient();

        if (! $client instanceof OAuthClient) {
            return $this->renderError('invalid_client', 'Unknown client.', Response::HTTP_BAD_REQUEST);
        }

        $companies = $this->eligibleCompanies->getFor($user);

        if ($companies === []) {
            return $this->renderError('access_denied', 'No companies available for this user.', Response::HTTP_FORBIDDEN);
        }

        $requestedScopeValues = array_map(
            static fn ($scope): string => $scope->getIdentifier(),
            $authRequest->getScopes(),
        );

        if ($requestedScopeValues === []) {
            $requestedScopeValues = [McpScope::Read->value];

            // Materialise the scope on the authorization request itself so the
            // approval loop in approveAndComplete() can grant it — otherwise
            // the issued token ends up with no scopes despite the consent page
            // showing read access.
            $authRequest->setScopes([new ScopeEntity(McpScope::Read->value)]);
        }

        if ($request->isMethod('POST')) {
            return $this->handleConsent($request, $server, $authRequest, $user, $client, $companies, $requestedScopeValues);
        }

        $activeCompanyId = $this->companySelector->getCompany()?->toRfc4122();

        // Single-company users with prior consent for the same scopes skip
        // the consent page. Multi-company users always see the picker so they
        // can pick which tenant this token should be bound to.
        $priorCompany = $this->resolvePriorConsentCompany($client, $user, $companies, $activeCompanyId, $requestedScopeValues);

        if ($priorCompany instanceof Company) {
            return $this->approveAndComplete($server, $authRequest, $user, $client, $priorCompany, $requestedScopeValues);
        }

        return new Response(
            $this->twig->render('@SolidInvoiceMcp/Authorize/consent.html.twig', [
                'client' => $client,
                'user' => $user,
                'companies' => $companies,
                'active_company_id' => $activeCompanyId,
                'requested_scopes' => $requestedScopeValues,
                'supports_write' => \in_array(McpScope::Write->value, $requestedScopeValues, true),
                'state' => $request->query->get('state'),
                'csrf_token_id' => self::CONSENT_CSRF_TOKEN_ID,
            ]),
        );
    }

    /**
     * @param list<Company> $companies
     * @param list<string>  $requestedScopes
     */
    private function resolvePriorConsentCompany(
        OAuthClient $client,
        User $user,
        array $companies,
        ?string $activeCompanyId,
        array $requestedScopes,
    ): ?Company {
        // Only skip the consent page for users with one company, or when an
        // active company is set and there's a prior grant for it. Multi-company
        // users without an active company always see the picker.
        $candidate = null;

        if (\count($companies) === 1) {
            $candidate = $companies[0];
        } elseif ($activeCompanyId !== null) {
            foreach ($companies as $company) {
                if ($company->getId()->toRfc4122() === $activeCompanyId) {
                    $candidate = $company;

                    break;
                }
            }
        }

        if (! $candidate instanceof Company) {
            return null;
        }

        return $this->consentService->hasPriorConsent($client, $user, $candidate, $requestedScopes)
            ? $candidate
            : null;
    }

    /**
     * @param list<string> $grantedScopeValues
     */
    private function approveAndComplete(
        AuthorizationServer $server,
        AuthorizationRequestInterface $authRequest,
        User $user,
        OAuthClient $client,
        Company $company,
        array $grantedScopeValues,
    ): Response {
        $grantedScopes = [];

        foreach ($grantedScopeValues as $scopeValue) {
            foreach ($authRequest->getScopes() as $scope) {
                if ($scope->getIdentifier() === $scopeValue) {
                    $grantedScopes[] = $scope;
                }
            }
        }

        $authRequest->setScopes($grantedScopes);

        $userId = $user->getId()?->toRfc4122();

        if ($userId === null || $userId === '') {
            return $this->renderError('server_error', 'User identifier unavailable.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $authRequest->setUser(new OAuthUserEntity($userId));
        $authRequest->setAuthorizationApproved(true);

        $this->pendingAuthorization->set($user, $company);

        try {
            $psrResponse = $server->completeAuthorizationRequest($authRequest, $this->psr17Factory->createResponse());
        } catch (OAuthServerException $exception) {
            $this->logger->notice('OAuth completeAuthorize rejected', ['reason' => $exception->getMessage()]);

            return $this->renderError($exception->getErrorType(), $exception->getMessage(), $exception->getHttpStatusCode());
        } finally {
            $this->pendingAuthorization->clear();
        }

        return (new HttpFoundationFactory())->createResponse($psrResponse);
    }

    /**
     * @param list<Company> $companies
     * @param list<string>  $requestedScopeValues
     */
    private function handleConsent(
        Request $request,
        AuthorizationServer $server,
        AuthorizationRequestInterface $authRequest,
        User $user,
        OAuthClient $client,
        array $companies,
        array $requestedScopeValues,
    ): Response {
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CONSENT_CSRF_TOKEN_ID, $csrfToken))) {
            return $this->renderError('invalid_request', 'CSRF token validation failed.', Response::HTTP_BAD_REQUEST);
        }

        if ($request->request->get('action') === 'deny') {
            $redirect = $authRequest->getRedirectUri() ?? ($client->getRedirectUris()[0] ?? null);

            if ($redirect === null) {
                return $this->renderError('access_denied', 'Authorization denied.', Response::HTTP_FORBIDDEN);
            }

            $query = http_build_query(array_filter([
                'error' => 'access_denied',
                'state' => $request->request->get('state'),
            ]));

            return new RedirectResponse(rtrim($redirect, '?') . (str_contains($redirect, '?') ? '&' : '?') . $query);
        }

        $companyId = (string) $request->request->get('company_id', '');

        if ($companyId === '') {
            return $this->renderError('invalid_request', 'A company must be selected.', Response::HTTP_BAD_REQUEST);
        }

        $company = $this->findEligibleCompany($companies, $companyId);

        if (! $company instanceof Company) {
            return $this->renderError('access_denied', 'Invalid company selected.', Response::HTTP_FORBIDDEN);
        }

        $grantWrite = (bool) $request->request->get('grant_write', false);

        $grantedScopeValues = [McpScope::Read->value];

        if ($grantWrite && \in_array(McpScope::Write->value, $requestedScopeValues, true)) {
            $grantedScopeValues[] = McpScope::Write->value;
        }

        // The grant is always persisted so the token/refresh flow can resolve
        // the bound company. The "remember" checkbox only controls whether
        // subsequent authorise requests skip the consent UI for this grant.
        $remember = $request->request->get('remember') === '1';
        $this->consentService->remember($client, $user, $company, $grantedScopeValues, $remember);

        return $this->approveAndComplete($server, $authRequest, $user, $client, $company, $grantedScopeValues);
    }

    /**
     * @param list<Company> $companies
     */
    private function findEligibleCompany(array $companies, string $companyId): ?Company
    {
        try {
            $ulid = Ulid::fromString($companyId);
        } catch (\InvalidArgumentException) {
            return null;
        }

        foreach ($companies as $company) {
            if ($company->getId()->equals($ulid)) {
                return $company;
            }
        }

        return null;
    }

    private function toPsrRequest(Request $request): \Psr\Http\Message\ServerRequestInterface
    {
        return $this->psrHttpFactory->createRequest($request);
    }

    private function renderError(string $code, string $description, int $status): Response
    {
        return new Response(
            $this->twig->render('@SolidInvoiceMcp/Authorize/error.html.twig', [
                'error' => $code,
                'error_description' => $description,
            ]),
            $status,
        );
    }
}
