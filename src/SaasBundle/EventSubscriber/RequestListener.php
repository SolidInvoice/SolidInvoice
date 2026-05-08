<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\EventSubscriber;

use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use function in_array;

final readonly class RequestListener implements EventSubscriberInterface
{
    private const array SKIPPED_ROUTES = [
        '_switch_company',
        '_view_quote_external',
        '_view_invoice_external',
        'billing_index',
        'saas_subscription_checkout',
        'saas_subscription_plans',
        'saas_subscription_choose',
        'saas_subscription_change',
        'saas_subscription_change_confirm',
        'saas_subscription_cancel_downgrade',
        'saas_payment_success',

        // Debug routes
        '_wdt',
        '_wdt_stylesheet',
        '_profiler',
        '_profiler_search',
        '_profiler_search_bar',
        '_profiler_search_results',
        '_profiler_router',
    ];

    public function __construct(
        private CompanySelector $companySelector,
        private CompanyRepository $companyRepository,
        private SubscriptionProviderInterface $subscriptionManager,
        private PlanRepositoryInterface $planRepository,
        private Environment $twig,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private ClockInterface $clock,
        #[Autowire(env: 'SOLIDINVOICE_SAAS_ONBOARDING_COUPON_CODE')]
        private string $onboardingCouponCode = '',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
            ResponseEvent::class => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $subscription = $this->getSubscription($event->getRequest());

        if (! $subscription instanceof Subscription) {
            return;
        }

        switch ($subscription->getStatus()) {
            case SubscriptionStatus::PENDING:
                $event->setResponse(
                    new Response(
                        $this->twig->render('@SolidInvoiceSaas/subscription/pending.html.twig', [
                            'subscription' => $subscription,
                            'plans' => $this->planRepository->findAllOrdered(),
                        ]),
                    )
                );
                break;
            case SubscriptionStatus::PAUSED:
                $event->setResponse(
                    new Response(
                        $this->twig->render('@SolidInvoiceSaas/subscription/paused.html.twig', [
                            'subscription' => $subscription,
                        ]),
                    )
                );
                break;
            case SubscriptionStatus::CANCELLED:
            case SubscriptionStatus::EXPIRED:
                if ($subscription->getEndDate() > $this->clock->now()) {
                    return;
                }

                $event->setResponse(
                    new Response(
                        $this->twig->render('@SolidInvoiceSaas/subscription/cancelled.html.twig', [
                            'subscription' => $subscription,
                        ]),
                    )
                );
                break;
            case SubscriptionStatus::TRIAL:
                if ($subscription->getEndDate() <= $this->clock->now()) {
                    $event->setResponse(
                        new Response(
                            $this->twig->render('@SolidInvoiceSaas/subscription/trial_expired.html.twig', [
                                'subscription' => $subscription,
                                'coupon_code' => $this->onboardingCouponCode,
                            ]),
                        )
                    );
                }
                break;
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $request->isMethod('GET') || $response->getStatusCode() !== Response::HTTP_OK) {
            return;
        }

        $subscription = $this->getSubscription($event->getRequest());
        if (! $subscription instanceof Subscription) {
            return;
        }

        if (($subscription->getStatus() !== SubscriptionStatus::TRIAL && $subscription->getStatus() !== SubscriptionStatus::CANCELLED) || $subscription->getEndDate() <= $this->clock->now()) {
            return;
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return;
        }

        $checkoutUrl = $this->urlGenerator->generate('saas_subscription_checkout');

        [$type, $icon, $title, $message, $ctaLabel] = match ($subscription->getStatus()) {
            SubscriptionStatus::CANCELLED => [
                'danger',
                'tabler:alert-circle',
                'Subscription Cancelled',
                'Your subscription has been cancelled. Your access will be revoked on ' . $subscription->getEndDate()->format('F j, Y') . '.',
                'Renew Subscription',
            ],
            SubscriptionStatus::TRIAL => [
                'warning',
                'tabler:clock-hour-4',
                'Trial Ending Soon',
                'Your trial is active until ' . $subscription->getEndDate()->format('F j, Y') . '. Please activate your subscription to continue.',
                'Activate Subscription',
            ],
        };

        $banner = $this->twig->render('@SolidInvoiceSaas/_alert_banner.html.twig', [
            'type' => $type,
            'icon' => $icon,
            'title' => $title,
            'message' => $message,
            'cta_label' => $ctaLabel,
            'cta_url' => $checkoutUrl,
        ]);

        $content = preg_replace(
            '/<div class="page-wrapper">/',
            '<div class="page-wrapper">' . $banner,
            $content,
            1
        );

        $response->setContent($content);
    }

    private function getSubscription(Request $request): ?Subscription
    {
        if ($request->attributes->get('_stateless') === true) {
            return null;
        }

        if (in_array($request->attributes->get('_route'), self::SKIPPED_ROUTES, true)) {
            return null;
        }

        if (null === $this->security->getUser()) {
            return null;
        }

        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return null;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company) {
            return null;
        }

        $subscription = $this->subscriptionManager->getSubscriptionFor($company);
        if (! $subscription instanceof Subscription) {
            return null;
        }

        return $subscription;
    }
}
