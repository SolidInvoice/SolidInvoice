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

namespace SolidInvoice\UserBundle\Security\Turnstile;

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Verifies a Cloudflare Turnstile token server-side.
 *
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation/
 * @see \SolidInvoice\UserBundle\Tests\Security\Turnstile\TurnstileVerifierTest
 */
final readonly class TurnstileVerifier
{
    private const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private HttpClientInterface $httpClient,
        private ToggleInterface $toggle,
        #[Autowire('%env(SOLIDINVOICE_TURNSTILE_SECRET_KEY)%')]
        private ?string $secretKey = null,
    ) {
    }

    /**
     * Returns true when the feature is disabled (never blocks), otherwise validates the token
     * against Cloudflare and fails closed on any missing data or transport error.
     */
    public function verify(?string $token, ?string $remoteIp): bool
    {
        if (! $this->toggle->isActive('turnstile_captcha')) {
            return true;
        }

        if ($this->secretKey === null || $this->secretKey === '' || $token === null || $token === '') {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ],
            ]);

            $data = $response->toArray(false);
        } catch (ExceptionInterface) {
            return false;
        }

        return ($data['success'] ?? false) === true;
    }
}
