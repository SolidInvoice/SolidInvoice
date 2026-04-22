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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\OAuth\ConsentService;
use SolidInvoice\McpBundle\OAuth\OAuthUserEntity;
use SolidInvoice\McpBundle\OAuth\PendingAuthorization;
use SolidInvoice\McpBundle\OAuth\ServerFactory;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

#[Route(path: '/oauth/authorize', name: 'mcp_oauth_authorize', methods: ['GET', 'POST'])]
final class Authorize
{
    public function __construct(
        private readonly ServerFactory $serverFactory,
        private readonly ConsentService $consentService,
        private readonly PendingAuthorization $pendingAuthorization,
        private readonly Security $security,
        private readonly CompanySelector $companySelector,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();

        if (! $user instanceof User) {
            return new RedirectResponse('/login?redirect=' . urlencode($request->getRequestUri()));
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

        $companies = $user->getCompanies();

        if ($companies->count() === 0) {
            return $this->renderError('access_denied', 'No companies available for this user.', Response::HTTP_FORBIDDEN);
        }

        $requestedScopeValues = array_map(
            static fn ($scope): string => $scope->getIdentifier(),
            $authRequest->getScopes(),
        );

        if ($requestedScopeValues === []) {
            $requestedScopeValues = [McpScope::Read->value];
        }

        if ($request->isMethod('POST')) {
            return $this->handleConsent($request, $server, $authRequest, $user, $client, $requestedScopeValues);
        }

        $activeCompanyId = $this->companySelector->getCompany()?->toRfc4122();

        return new Response(
            $this->twig->render('@SolidInvoiceMcp/Authorize/consent.html.twig', [
                'client' => $client,
                'user' => $user,
                'companies' => $companies,
                'active_company_id' => $activeCompanyId,
                'requested_scopes' => $requestedScopeValues,
                'supports_write' => \in_array(McpScope::Write->value, $requestedScopeValues, true),
                'state' => $request->query->get('state'),
            ]),
        );
    }

    /**
     * @param list<string> $requestedScopeValues
     */
    private function handleConsent(
        Request $request,
        AuthorizationServer $server,
        AuthorizationRequestInterface $authRequest,
        User $user,
        OAuthClient $client,
        array $requestedScopeValues,
    ): Response {
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

        if ($companyId === '' || ! Ulid::isValid($companyId)) {
            return $this->renderError('invalid_request', 'A company must be selected.', Response::HTTP_BAD_REQUEST);
        }

        $company = $this->findUserCompany($user, $companyId);

        if (! $company instanceof Company) {
            return $this->renderError('access_denied', 'Invalid company selected.', Response::HTTP_FORBIDDEN);
        }

        $grantWrite = (bool) $request->request->get('grant_write', false);

        $grantedScopeValues = [McpScope::Read->value];

        if ($grantWrite && \in_array(McpScope::Write->value, $requestedScopeValues, true)) {
            $grantedScopeValues[] = McpScope::Write->value;
        }

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

        if ($request->request->get('remember') === '1') {
            $this->consentService->remember($client, $user, $company, $grantedScopeValues);
        }

        $this->pendingAuthorization->set($user, $company);

        try {
            $psrResponse = $server->completeAuthorizationRequest($authRequest, (new Psr17Factory())->createResponse());
        } catch (OAuthServerException $exception) {
            $this->logger->notice('OAuth completeAuthorize rejected', ['reason' => $exception->getMessage()]);

            return $this->renderError($exception->getErrorType(), $exception->getMessage(), $exception->getHttpStatusCode());
        } finally {
            $this->pendingAuthorization->clear();
        }

        return (new HttpFoundationFactory())->createResponse($psrResponse);
    }

    private function findUserCompany(User $user, string $companyId): ?Company
    {
        $ulid = Ulid::fromString($companyId);

        foreach ($user->getCompanies() as $company) {
            if ($company->getId()?->equals($ulid)) {
                return $company;
            }
        }

        return null;
    }

    private function toPsrRequest(Request $request): \Psr\Http\Message\ServerRequestInterface
    {
        $psr17 = new Psr17Factory();
        $factory = new PsrHttpFactory($psr17, $psr17, $psr17, $psr17);

        return $factory->createRequest($request);
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
