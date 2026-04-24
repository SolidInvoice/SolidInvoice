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

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Restricts the auth-code grant to S256 PKCE only (no plain method, no PKCE-less flows).
 */
final class StrictS256AuthCodeGrant extends AuthCodeGrant
{
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface
    {
        $codeChallenge = $this->getQueryStringParameter('code_challenge', $request);

        if ($codeChallenge === null) {
            throw OAuthServerException::invalidRequest(
                'code_challenge',
                'PKCE is required. Provide a code_challenge with code_challenge_method=S256.',
            );
        }

        $challengeMethod = $this->getQueryStringParameter('code_challenge_method', $request);

        if ($challengeMethod !== 'S256') {
            throw OAuthServerException::invalidRequest(
                'code_challenge_method',
                'Only S256 code_challenge_method is supported.',
            );
        }

        return parent::validateAuthorizationRequest($request);
    }
}
