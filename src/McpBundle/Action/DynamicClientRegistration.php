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

use JsonException;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Uri;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\McpBundle\Security\McpScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/oauth/register', name: 'mcp_oauth_register', methods: ['POST'])]
final class DynamicClientRegistration
{
    public function __construct(
        private readonly OAuthClientRepository $clientRepository,
        private readonly ?RateLimiterFactory $mcpOauthRegisterLimiter = null,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if ($this->mcpOauthRegisterLimiter !== null) {
            $limiter = $this->mcpOauthRegisterLimiter->create($request->getClientIp() ?? 'unknown');
            $limit = $limiter->consume();

            if (! $limit->isAccepted()) {
                $retryAfter = $limit->getRetryAfter();
                $retryAfterSeconds = max(0, $retryAfter->getTimestamp() - time());

                return new JsonResponse(
                    ['error' => 'rate_limited', 'error_description' => 'Too many registration attempts. Try again later.'],
                    Response::HTTP_TOO_MANY_REQUESTS,
                    [
                        'Retry-After' => (string) $retryAfterSeconds,
                        'X-RateLimit-Reset' => (string) $retryAfter->getTimestamp(),
                    ],
                );
            }
        }

        try {
            $payload = $this->decodePayload($request);
        } catch (JsonException $exception) {
            return $this->error('invalid_request', 'Invalid JSON payload: ' . $exception->getMessage());
        }

        $redirectUris = $payload['redirect_uris'] ?? null;

        if (! \is_array($redirectUris) || $redirectUris === []) {
            return $this->error('invalid_redirect_uri', 'At least one redirect_uri is required.');
        }

        foreach ($redirectUris as $uri) {
            if (! \is_string($uri)) {
                return $this->error('invalid_redirect_uri', sprintf('Invalid redirect URI: %s', (string) $uri));
            }

            try {
                $parsed = Uri::new($uri);
            } catch (SyntaxError) {
                return $this->error('invalid_redirect_uri', sprintf('Invalid redirect URI: %s', $uri));
            }

            $isHttps = $parsed->getScheme() === 'https';
            $isLocalhost = $parsed->getScheme() === 'http'
                && \in_array($parsed->getHost(), ['localhost', '127.0.0.1', '[::1]'], true);

            if (! $isHttps && ! $isLocalhost) {
                return $this->error('invalid_redirect_uri', sprintf('Redirect URI must use HTTPS or be a localhost HTTP URI: %s', $uri));
            }
        }

        $name = $payload['client_name'] ?? 'Unnamed MCP Client';

        if (! \is_string($name) || trim($name) === '' || mb_strlen($name) > 255) {
            return $this->error('invalid_client_metadata', 'client_name must be a non-empty string up to 255 characters.');
        }

        $name = trim($name);

        $tokenEndpointAuthMethod = (string) ($payload['token_endpoint_auth_method'] ?? 'none');

        if (! \in_array($tokenEndpointAuthMethod, ['none', 'client_secret_basic', 'client_secret_post'], true)) {
            return $this->error('invalid_client_metadata', 'Unsupported token_endpoint_auth_method.');
        }

        $grantTypes = $payload['grant_types'] ?? ['authorization_code', 'refresh_token'];

        if (! \is_array($grantTypes)) {
            return $this->error('invalid_client_metadata', 'grant_types must be an array.');
        }

        $allowedGrants = ['authorization_code', 'refresh_token'];

        foreach ($grantTypes as $grantType) {
            if (! \in_array($grantType, $allowedGrants, true)) {
                return $this->error('invalid_client_metadata', sprintf('Unsupported grant_type: %s', (string) $grantType));
            }
        }

        $requestedScopes = isset($payload['scope']) && \is_string($payload['scope'])
            ? array_values(array_filter(explode(' ', $payload['scope'])))
            : ['mcp:read'];

        $validScopes = array_values(array_intersect($requestedScopes, McpScope::values()));

        if ($validScopes === []) {
            $validScopes = ['mcp:read'];
        }

        $client = new OAuthClient();
        $client->setName($name);
        $client->setRedirectUris(array_values($redirectUris));
        $client->setGrantTypes(array_values($grantTypes));
        $client->setScopes($validScopes);
        $client->setTokenEndpointAuthMethod($tokenEndpointAuthMethod);

        $plainSecret = null;

        if ($tokenEndpointAuthMethod !== 'none') {
            $plainSecret = bin2hex(random_bytes(32));
            $client->setSecretHash(password_hash($plainSecret, PASSWORD_BCRYPT));
        }

        $this->clientRepository->save($client);

        $response = [
            'client_id' => $client->getIdentifier(),
            'client_id_issued_at' => $client->getCreated()?->getTimestamp() ?? time(),
            'client_name' => $client->getName(),
            'redirect_uris' => $client->getRedirectUris(),
            'grant_types' => $client->getGrantTypes(),
            'scope' => implode(' ', $client->getScopes()),
            'token_endpoint_auth_method' => $client->getTokenEndpointAuthMethod(),
        ];

        if ($plainSecret !== null) {
            $response['client_secret'] = $plainSecret;
            // RFC 7591 §3.2.1: client_secret_expires_at is REQUIRED whenever
            // client_secret is returned. 0 signals a non-expiring secret.
            $response['client_secret_expires_at'] = 0;
        }

        return new JsonResponse($response, Response::HTTP_CREATED);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function decodePayload(Request $request): array
    {
        $body = $request->getContent();

        if ($body === '') {
            return [];
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (! \is_array($data)) {
            throw new JsonException('Request body must be a JSON object.');
        }

        return $data;
    }

    private function error(string $code, string $description): JsonResponse
    {
        return new JsonResponse(
            ['error' => $code, 'error_description' => $description],
            Response::HTTP_BAD_REQUEST,
        );
    }
}
