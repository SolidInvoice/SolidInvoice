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

namespace SolidInvoice\UserBundle\Validator\Constraints;

use SolidInvoice\UserBundle\Security\Turnstile\TurnstileVerifier;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TurnstileValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TurnstileVerifier $verifier,
        private readonly RequestStack $requestStack,
        private readonly ToggleInterface $toggle,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Turnstile) {
            throw new UnexpectedTypeException($constraint, Turnstile::class);
        }

        if (! $this->toggle->isActive('turnstile_captcha')) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (! $request instanceof Request) {
            $this->context->buildViolation($constraint->message)->addViolation();

            return;
        }

        $token = $request->request->getString('cf-turnstile-response');

        if (! $this->verifier->verify($token, $request->getClientIp())) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
