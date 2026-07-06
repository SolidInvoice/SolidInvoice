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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Override;
use SolidInvoice\CoreBundle\Demo\DemoMode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\DemoExtensionTest
 */
final class DemoExtension extends AbstractExtension
{
    public function __construct(
        private readonly DemoMode $demoMode,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('demo_enabled', $this->isEnabled(...)),
            new TwigFunction('demo_username', $this->username(...)),
            new TwigFunction('demo_password', $this->password(...)),
            new TwigFunction('demo_signup_url', $this->signupUrl(...)),
        ];
    }

    public function isEnabled(): bool
    {
        return $this->demoMode->isEnabled();
    }

    public function username(): ?string
    {
        return $this->demoMode->username();
    }

    public function password(): ?string
    {
        return $this->demoMode->password();
    }

    public function signupUrl(): ?string
    {
        return $this->demoMode->signupUrl();
    }
}
