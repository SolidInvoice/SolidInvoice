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

namespace SolidInvoice\SaasBundle\Action;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidInvoice\SaasBundle\Analytics\DataLayerQueue;
use SolidInvoice\SaasBundle\Service\SubscriptionService;
use SolidInvoice\SaasBundle\Trial\TrialCta;
use SolidInvoice\SaasBundle\Trial\TrialOffer;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Exception\TrialAlreadyExistsException;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use function preg_match;
use function str_starts_with;
use function strtolower;

/**
 * Starts the opt-in trial, or moves a running one up a plan.
 *
 * GET renders a confirm card (email links cannot POST); POST does the work.
 * Gates link here with `feature` (the trial runs on the cheapest plan that has
 * it) and the pricing page with `plan`. `return` brings the user back to the
 * page they were on.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Functional\StartTrialActionTest
 */
final class StartTrialAction extends AbstractController
{
    private const string CSRF_TOKEN = 'trial_start';

    public function __construct(
        private readonly TrialOffer $trialOffer,
        private readonly TrialManagerInterface $trialManager,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriptionService $subscriptionService,
        private readonly PlanRepositoryInterface $planRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly DataLayerQueue $dataLayerQueue,
        private readonly Telemetry $telemetry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        $subscription = $this->subscriptionService->current();

        if (! $user instanceof User || ! $subscription instanceof Subscription) {
            return $this->redirectToRoute('saas_subscription_plans');
        }

        $input = $request->isMethod(Request::METHOD_POST) ? $request->request : $request->query;
        $feature = $input->getString('feature');
        $planId = $input->getString('plan');
        $return = $this->safeReturnPath($input->getString('return'));

        $plan = match (true) {
            $feature !== '' => $this->trialOffer->planForFeature($feature, $subscription),
            $planId !== '' => $this->trialOffer->planById($planId, $subscription),
            default => null,
        };

        // The same decision the gates use to show the offer.
        $offer = $plan instanceof Plan ? $this->trialOffer->offer($plan, $user, $subscription) : null;

        if (! $offer instanceof TrialCta) {
            return $this->redirectToRoute('saas_subscription_plans');
        }

        $plan = $offer->plan;
        $movingUp = $offer->movingUp;

        if ($request->isMethod(Request::METHOD_GET)) {
            return $this->render('@SolidInvoiceSaas/subscription/trial_start.html.twig', [
                'plan' => $plan,
                'trialDays' => $offer->days,
                'trialEnd' => $subscription->getEndDate(),
                'freePlan' => $this->planRepository->findFree(),
                'movingUp' => $movingUp,
                'feature' => $feature,
                'planId' => $planId,
                'return' => $return,
                'csrfToken' => self::CSRF_TOKEN,
            ]);
        }

        if (! $this->isCsrfTokenValid(self::CSRF_TOKEN, (string) $request->request->get('_token', ''))) {
            $this->addFlash('error', 'saas.flash.invalid_token');

            return $this->redirectToRoute('saas_subscription_plans');
        }

        if ($movingUp) {
            // Same end date: moving up widens the trial, it never lengthens it.
            $this->subscriptionManager->changePlan($subscription, $plan);
        } else {
            try {
                $this->entityManager->wrapInTransaction(function () use ($user, $subscription, $plan): void {
                    $this->subscriptionManager->changePlan($subscription, $plan);
                    $this->subscriptionManager->startTrial($subscription);

                    $this->trialManager->createTrial($user, $subscription);
                });
            } catch (TrialAlreadyExistsException | UniqueConstraintViolationException) {
                // A second tab got there first; that trial stands. The unique
                // index catches the tab that passed the check at the same time.
                return $this->redirectToRoute('saas_subscription_plans');
            }
        }

        $event = $movingUp ? 'trial_upgrade' : 'trial_start';
        $this->dataLayerQueue->push(['event' => $event, 'feature' => $feature, 'plan' => strtolower($plan->getName())]);
        $this->telemetry->event($movingUp ? TelemetryEvent::SaasTrialUpgraded : TelemetryEvent::SaasTrialStarted, [
            'feature' => $feature,
            'plan' => strtolower($plan->getName()),
        ]);

        $this->addFlash('success', $this->translator->trans(
            $movingUp ? 'saas.flash.trial_upgraded' : 'saas.flash.trial_started',
            ['%plan%' => $plan->getName(), '%date%' => $subscription->getEndDate()->format('F j')],
        ));

        return $return !== null ? new RedirectResponse($return) : $this->redirectToRoute('_dashboard');
    }

    /**
     * Only a path on this host: `//evil.test` and `/\evil.test` are both read
     * by browsers as another host, and browsers strip tabs and newlines, so
     * `/\t/evil.test` becomes `//evil.test`. Control characters and spaces are
     * rejected outright.
     */
    private function safeReturnPath(string $return): ?string
    {
        if (! str_starts_with($return, '/') || str_starts_with($return, '//') || preg_match('/[\x00-\x20\x7f\\\\]/', $return) === 1) {
            return null;
        }

        return $return;
    }
}
