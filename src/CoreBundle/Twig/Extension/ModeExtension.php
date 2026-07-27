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
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes the application run mode (self-hosted / demo / saas) to Twig templates.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\ModeExtensionTest
 */
final class ModeExtension extends AbstractExtension
{
    public function __construct(
        private readonly ModeResolver $modeResolver,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_mode', $this->appMode(...)),
            new TwigFunction('is_demo', $this->isDemo(...)),
            new TwigFunction('is_saas', $this->isSaas(...)),
            new TwigFunction('demo_username', $this->demoUsername(...)),
            new TwigFunction('demo_password', $this->demoPassword(...)),
            new TwigFunction('demo_signup_url', $this->demoSignupUrl(...)),
        ];
    }

    public function appMode(): string
    {
        return $this->modeResolver->current()->value;
    }

    public function isDemo(): bool
    {
        return $this->modeResolver->isDemo();
    }

    public function isSaas(): bool
    {
        return $this->modeResolver->isSaas();
    }

    public function demoUsername(): ?string
    {
        return $this->modeResolver->demoUsername();
    }

    public function demoPassword(): ?string
    {
        return $this->modeResolver->demoPassword();
    }

    public function demoSignupUrl(): ?string
    {
        return $this->modeResolver->demoSignupUrl();
    }
}
