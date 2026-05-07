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

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EmailVerificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly EmailVerificationGateInterface $gate,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_email_verification_gated', $this->isEmailVerificationGated(...)),
            new TwigFunction('email_verification_message', $this->emailVerificationMessage(...)),
        ];
    }

    public function isEmailVerificationGated(): bool
    {
        return $this->gate->isGated();
    }

    public function emailVerificationMessage(string $action): string
    {
        return $this->gate->reason($action);
    }
}
