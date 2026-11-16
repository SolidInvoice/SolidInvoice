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
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Called in the background when the dashboard prompt closes, so it answers with no content
 * rather than a redirect.
 *
 * @see \SolidInvoice\UserBundle\Tests\Functional\Affiliate\DismissPromptTest
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final readonly class DismissPrompt
{
    public const string CSRF_TOKEN_ID = 'dismiss_affiliate_prompt';

    public function __construct(
        private AffiliateProgram $affiliateProgram,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private Telemetry $telemetry,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (! $this->affiliateProgram->isEnabled()) {
            throw new NotFoundHttpException();
        }

        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $request->request->getString('_token')))) {
            throw new AccessDeniedHttpException();
        }

        /** @var User $user */
        $user = $this->security->getUser();

        $this->affiliateProgram->dismissPrompt($user);
        $this->telemetry->event(TelemetryEvent::AffiliatePromptDismissed);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
