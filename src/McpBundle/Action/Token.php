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

use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Log\LoggerInterface;
use SolidInvoice\McpBundle\OAuth\ServerFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/oauth/token', name: 'mcp_oauth_token', methods: ['POST'])]
final class Token
{
    public function __construct(
        private readonly ServerFactory $serverFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $server = $this->serverFactory->createAuthorizationServer();

        $psr17 = new Psr17Factory();
        $factory = new PsrHttpFactory($psr17, $psr17, $psr17, $psr17);
        $psrRequest = $factory->createRequest($request);
        $psrResponse = $psr17->createResponse();

        try {
            $response = $server->respondToAccessTokenRequest($psrRequest, $psrResponse);
        } catch (OAuthServerException $exception) {
            $this->logger->notice('OAuth token request failed', [
                'reason' => $exception->getMessage(),
                'error' => $exception->getErrorType(),
            ]);

            $response = $exception->generateHttpResponse($psrResponse);
        }

        return (new HttpFoundationFactory())->createResponse($response);
    }
}
