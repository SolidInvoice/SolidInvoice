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

namespace SolidInvoice\CoreBundle\Mode;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use function sprintf;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Mode\ModeResolverTest
 */
final class ModeResolver
{
    /**
     * Capabilities DENIED per mode. Modes not listed allow everything at this layer
     * (SaaS plan/subscription restrictions live in the separate FeatureGate layer).
     *
     * @var array<string, list<Capability>>
     */
    private const array DENIED = [
        ApplicationMode::Demo->value => [
            Capability::UserRegistration,
            Capability::RealEmailDelivery,
            Capability::RealNotificationDelivery,
            Capability::OnlinePaymentCapture,
            Capability::CredentialChange,
        ],
    ];

    public function __construct(
        #[Autowire('%env(SOLIDINVOICE_MODE)%')]
        private readonly string $mode = 'self-hosted',
        #[Autowire('%env(SOLIDINVOICE_DEMO_USERNAME)%')]
        private readonly string $demoUsername = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_PASSWORD)%')]
        private readonly string $demoPassword = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_SIGNUP_URL)%')]
        private readonly string $demoSignupUrl = '',
    ) {
    }

    public function current(): ApplicationMode
    {
        return ApplicationMode::tryFrom($this->mode)
            ?? throw new InvalidArgumentException(sprintf(
                'Invalid SOLIDINVOICE_MODE "%s". Expected one of: self-hosted, demo, saas.',
                $this->mode,
            ));
    }

    public function is(ApplicationMode $mode): bool
    {
        return $this->current() === $mode;
    }

    public function isSelfHosted(): bool
    {
        return $this->is(ApplicationMode::SelfHosted);
    }

    public function isDemo(): bool
    {
        return $this->is(ApplicationMode::Demo);
    }

    public function isSaas(): bool
    {
        return $this->is(ApplicationMode::Saas);
    }

    public function allows(Capability $capability): bool
    {
        return ! in_array($capability, self::DENIED[$this->current()->value] ?? [], true);
    }

    public function demoUsername(): ?string
    {
        return $this->isDemo() && '' !== $this->demoUsername ? $this->demoUsername : null;
    }

    public function demoPassword(): ?string
    {
        return $this->isDemo() && '' !== $this->demoPassword ? $this->demoPassword : null;
    }

    public function demoSignupUrl(): ?string
    {
        return $this->isDemo() && '' !== $this->demoSignupUrl ? $this->demoSignupUrl : null;
    }
}
