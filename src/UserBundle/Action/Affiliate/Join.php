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

namespace SolidInvoice\UserBundle\Action\Affiliate;

use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidInvoice\UserBundle\Affiliate\AffiliateSource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The "Join the affiliate program" button. It passes through here so the click can be counted
 * before the browser leaves for the configured sign-up URL; the destination is never taken from
 * the request.
 *
 * @see \SolidInvoice\UserBundle\Tests\Functional\Affiliate\ReferAndEarnTest
 */
final readonly class Join
{
    public function __construct(
        private AffiliateProgram $affiliateProgram,
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(AffiliateSource $source): RedirectResponse
    {
        if (! $this->affiliateProgram->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $this->telemetry->event(TelemetryEvent::AffiliateCtaClicked, ['source' => $source->value]);

        return new RedirectResponse($this->affiliateProgram->getSignupUrl());
    }
}
