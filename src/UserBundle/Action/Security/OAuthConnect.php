<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Action\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use function array_key_exists;

final class OAuthConnect extends AbstractController
{
    public const ROUTE = '_oauth_connect';

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly ToggleInterface $toggle,
    ) {
    }

    public function __invoke(string $service): RedirectResponse
    {
        if (! $this->toggle->isActive($service . '_oauth_login') || ! array_key_exists($service, $this->clientRegistry->getEnabledClientKeys())) {
            throw $this->createNotFoundException();
        }

        return $this->clientRegistry
            ->getClient($service)
            ->redirect($this->getScopes($service), []);
    }

    /**
     * @return list<string>
     */
    private function getScopes(string $service): array
    {
        return match ($service) {
            'google' => ['profile', 'email'],
            default => throw $this->createNotFoundException('Service not found'),
        };
    }
}
