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
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @see \SolidInvoice\UserBundle\Tests\Functional\Affiliate\ReferAndEarnTest
 */
final readonly class ReferAndEarn
{
    public function __construct(
        private AffiliateProgram $affiliateProgram,
        private Telemetry $telemetry,
    ) {
    }

    /**
     * @return array{}
     */
    #[Template('@SolidInvoiceUser/Affiliate/index.html.twig')]
    public function __invoke(Request $request): array
    {
        if (! $this->affiliateProgram->isEnabled()) {
            throw new NotFoundHttpException();
        }

        $this->telemetry->event(TelemetryEvent::AffiliatePageViewed, [
            'source' => AffiliateSource::tryFrom($request->query->getString('source'))?->value,
        ]);

        return [];
    }
}
