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
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use function in_array;
use function str_replace;

final readonly class RequestListener implements EventSubscriberInterface
{
    private const array SKIPPED_ROUTES = [
        '_switch_company',
        '_create_company',
        '_view_quote_external',
        '_view_invoice_external',
        'saas_subscription_checkout',
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
        private SubscriptionManager $subscriptionManager,
        private Environment $twig,
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private ClockInterface $clock,
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
                            $this->twig->render('@SolidInvoiceSaas/subscription/pending.html.twig', [
                                'subscription' => $subscription,
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

        $checkoutUrl = $this->urlGenerator->generate('saas_subscription_checkout');

        $message = match ($subscription->getStatus()) {
            SubscriptionStatus::CANCELLED => '<strong>Subscription Canceled</strong> - Your subscription has been canceled. Your access will be revoked on ' . $subscription->getEndDate()->format('Y-m-d H:i:s') . '.<br /><a href="' . $checkoutUrl . '" class="btn btn-default btn-sm">Renew now</a> to avoid losing access.',
            SubscriptionStatus::TRIAL => '<strong>Trial Ending Soon</strong> - Your trial is active until ' . $subscription->getEndDate()->format('Y-m-d H:i:s') . '.<br />Please <a href="' . $checkoutUrl . '" class="btn btn-default btn-sm">activate</a> your subscription now.',
        };

        $content = str_replace(
            '<div class="wrapper">',
            '<div class="wrapper"><div class="main-header bg-yellow p-2 text-center text-white">' . $message . '</div>',
            $content,
        );

        $response->setContent($content);
    }

    private function getSubscription(Request $request): ?Subscription
    {
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
