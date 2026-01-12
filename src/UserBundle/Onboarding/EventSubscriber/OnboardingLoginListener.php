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

namespace SolidInvoice\UserBundle\Onboarding\EventSubscriber;

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Onboarding\Manager\OnboardingManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use function count;

/**
 * Redirect users to onboarding after login if not completed
 *
 * IMPORTANT: Users invited to join an existing company should NOT go through onboarding.
 * Those users already have a company assigned during registration.
 *
 * This listener runs only once per login, avoiding the performance overhead of checking
 * onboarding status on every request.
 */
final class OnboardingLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly OnboardingManager $onboardingManager,
        private readonly RouterInterface $router,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => ['onLoginSuccess', -10], // Run after default handlers
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        // Only handle User entities
        if (! $user instanceof User) {
            return;
        }

        // Check if user was invited (has a company already)
        // Invited users skip onboarding entirely
        if (count($user->getCompanies()) > 0) {
            // User has a company - they were either invited or completed onboarding
            // Make sure onboarding is marked complete if not already
            if (! $this->onboardingManager->isOnboardingComplete($user)) {
                $this->onboardingManager->dismissOnboarding($user);
            }
            return;
        }

        // Check if onboarding is complete
        if (! $this->onboardingManager->isOnboardingComplete($user)) {
            // User needs to complete onboarding - redirect them
            $url = $this->router->generate('_onboarding');
            $response = new RedirectResponse($url);
            $event->setResponse($response);
        }
    }
}
