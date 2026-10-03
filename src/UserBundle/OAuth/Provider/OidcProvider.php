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

namespace SolidInvoice\UserBundle\OAuth\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\GenericProvider;
use RuntimeException;
use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function rtrim;
use function sprintf;

/**
 * Generic OpenID Connect provider built on top of {@see GenericProvider}.
 *
 * Endpoints are resolved from the issuer's discovery document
 * (`{issuer}/.well-known/openid-configuration`) unless they are supplied
 * explicitly, so any OIDC compliant identity provider can be used without a
 * dedicated client library.
 *
 * @see https://openid.net/specs/openid-connect-discovery-1_0.html
 */
final class OidcProvider extends GenericProvider
{
    private const string DISCOVERY_PATH = '/.well-known/openid-configuration';

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        $options = self::resolveEndpoints($options, $collaborators['httpClient'] ?? null);

        // OIDC identifies the user by the `sub` claim rather than the OAuth2 `id`.
        if (! array_key_exists('responseResourceOwnerId', $options)) {
            $options['responseResourceOwnerId'] = 'sub';
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private static function resolveEndpoints(array $options, ?ClientInterface $httpClient): array
    {
        $isMissing = static fn (string $key): bool => ! isset($options[$key]) || $options[$key] === '';

        if (! $isMissing('urlAuthorize') && ! $isMissing('urlAccessToken') && ! $isMissing('urlResourceOwnerDetails')) {
            return $options;
        }

        $issuer = $options['issuer'] ?? null;

        if (! is_string($issuer) || $issuer === '') {
            throw new InvalidArgumentException('The "issuer" option is required to discover the OpenID Connect endpoints.');
        }

        $configuration = self::fetchDiscoveryDocument($issuer, $httpClient);

        foreach ([
            'urlAuthorize' => 'authorization_endpoint',
            'urlAccessToken' => 'token_endpoint',
            'urlResourceOwnerDetails' => 'userinfo_endpoint',
        ] as $option => $claim) {
            if (! $isMissing($option)) {
                continue;
            }

            $endpoint = $configuration[$claim] ?? null;

            if (! is_string($endpoint) || $endpoint === '') {
                throw new RuntimeException(sprintf('The OpenID Connect discovery document for "%s" is missing the "%s" endpoint.', $issuer, $claim));
            }

            $options[$option] = $endpoint;
        }

        return $options;
    }

    /**
     * @return array<string, mixed>
     */
    private static function fetchDiscoveryDocument(string $issuer, ?ClientInterface $httpClient): array
    {
        $httpClient ??= new Client(['timeout' => 10.0, 'http_errors' => false]);

        $response = $httpClient->request('GET', rtrim($issuer, '/') . self::DISCOVERY_PATH, [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $configuration = json_decode((string) $response->getBody(), true);

        if (! is_array($configuration)) {
            throw new RuntimeException(sprintf('Could not read the OpenID Connect discovery document for "%s".', $issuer));
        }

        return $configuration;
    }
}
