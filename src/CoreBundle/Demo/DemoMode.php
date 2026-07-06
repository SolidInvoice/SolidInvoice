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

namespace SolidInvoice\CoreBundle\Demo;

use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class DemoMode
{
    public function __construct(
        private readonly ToggleInterface $toggle,
        #[Autowire('%env(SOLIDINVOICE_DEMO_USERNAME)%')]
        private readonly string $username = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_PASSWORD)%')]
        private readonly string $password = '',
        #[Autowire('%env(SOLIDINVOICE_DEMO_SIGNUP_URL)%')]
        private readonly string $signupUrl = '',
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->toggle->isActive('demo_enabled');
    }

    public function username(): ?string
    {
        return '' !== $this->username ? $this->username : null;
    }

    public function password(): ?string
    {
        return '' !== $this->password ? $this->password : null;
    }

    public function signupUrl(): ?string
    {
        return '' !== $this->signupUrl ? $this->signupUrl : null;
    }
}
