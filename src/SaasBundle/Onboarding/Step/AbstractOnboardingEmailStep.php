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

namespace SolidInvoice\SaasBundle\Onboarding\Step;

use SolidInvoice\SaasBundle\Onboarding\OnboardingContext;
use SolidInvoice\SaasBundle\Onboarding\OnboardingEmailStepInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractOnboardingEmailStep implements OnboardingEmailStepInterface
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    ) {
    }

    public function shouldSend(OnboardingContext $context): bool
    {
        return true;
    }

    public function createEmail(OnboardingContext $context): TemplatedEmail
    {
        $email = new TemplatedEmail();

        $email->subject($this->translator->trans(
            'onboarding.' . static::key() . '.subject',
            [],
            'email',
        ));

        $email->htmlTemplate('@SolidInvoiceSaas/Email/Onboarding/' . static::key() . '.html.twig');
        $email->textTemplate('@SolidInvoiceSaas/Email/Onboarding/' . static::key() . '.txt.twig');

        $email->context($this->templateContext($context));

        return $email;
    }

    /**
     * @return array<string, mixed>
     */
    protected function templateContext(OnboardingContext $context): array
    {
        return [
            'user' => $context->user,
            'company' => $context->company,
            'subscription' => $context->subscription,
            'plan' => $context->plan,
            'trial_start' => $context->trialStart,
            'trial_end' => $context->trialEnd,
        ];
    }
}
