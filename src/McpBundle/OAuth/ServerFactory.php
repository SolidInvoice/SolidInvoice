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

namespace SolidInvoice\McpBundle\OAuth;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\McpBundle\Repository\McpRefreshTokenRepository;
use SolidInvoice\McpBundle\Repository\McpScopeRepository;
use SolidInvoice\McpBundle\Repository\OAuthAuthCodeRepository;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;

final class ServerFactory implements ServerFactoryInterface
{
    public function __construct(
        private readonly KeyManager $keyManager,
        private readonly OAuthClientRepository $clientRepository,
        private readonly McpAccessTokenRepository $accessTokenRepository,
        private readonly McpRefreshTokenRepository $refreshTokenRepository,
        private readonly OAuthAuthCodeRepository $authCodeRepository,
        private readonly McpScopeRepository $scopeRepository,
        private readonly string $accessTokenTtl = 'PT1H',
        private readonly string $refreshTokenTtl = 'P30D',
        private readonly string $authCodeTtl = 'PT10M',
    ) {
    }

    public function createAuthorizationServer(): AuthorizationServer
    {
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            $this->keyManager->getPrivateKey(),
            $this->keyManager->getEncryptionKey(),
        );

        $authCodeGrant = new StrictS256AuthCodeGrant(
            $this->authCodeRepository,
            $this->refreshTokenRepository,
            new DateInterval($this->authCodeTtl),
        );
        $authCodeGrant->setRefreshTokenTTL(new DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($authCodeGrant, new DateInterval($this->accessTokenTtl));

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL(new DateInterval($this->refreshTokenTtl));

        $server->enableGrantType($refreshTokenGrant, new DateInterval($this->accessTokenTtl));

        return $server;
    }

    public function createResourceServer(): ResourceServer
    {
        return new ResourceServer(
            $this->accessTokenRepository,
            $this->keyManager->getPublicKey(),
        );
    }
}
