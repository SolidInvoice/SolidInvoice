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

namespace SolidInvoice\DashboardBundle\Action;

use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class DismissOnboardingChecklist
{
    public function __construct(
        private readonly ChecklistManager $checklistManager,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($this->csrfTokenManager->isTokenValid(new CsrfToken('dismiss_checklist', (string) $request->request->get('_token')))) {
            $this->checklistManager->dismiss($user);

            $session = $this->requestStack->getSession();
            if (method_exists($session, 'getFlashBag')) {
                $session->getFlashBag()->add('success', 'dashboard.checklist.dismissed_message');
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('_dashboard'));
    }
}
