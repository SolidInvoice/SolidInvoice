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

use DateTimeImmutable;
use DateTimeZone;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;
use function assert;
use function in_array;
use function str_replace;

final readonly class RequestListener implements EventSubscriberInterface
{
    private const array SKIPPED_ROUTES = [
        '_switch_company',
        '_view_quote_external',
        '_view_invoice_external',

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

        switch ($subscription?->getStatus()) {
            case SubscriptionStatus::PENDING:
                $user = $this->security->getUser();
                assert($user instanceof User);

                $checkoutUrl = $this->subscriptionManager->getCheckoutUrl($subscription, ['email' => $user->getEmail()]);
                $event->setResponse(
                    new Response(
                        $this->twig->render('@SolidInvoiceSaas/subscription/pending.html.twig', [
                            'subscription' => $subscription,
                            'checkoutUrl' => $checkoutUrl,
                        ]),
                    )
                );
                break;
            case SubscriptionStatus::CANCELLED:
            case SubscriptionStatus::EXPIRED:
                if ($subscription->getEndDate() > new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                    return;
                }

                $user = $this->security->getUser();
                assert($user instanceof User);

                $checkoutUrl = $this->subscriptionManager->getCheckoutUrl($subscription, ['email' => $user->getEmail()]);
                $event->setResponse(
                    new Response(
                        $this->twig->render('@SolidInvoiceSaas/subscription/cancelled.html.twig', [
                            'subscription' => $subscription,
                            'checkoutUrl' => $checkoutUrl,
                        ]),
                    )
                );
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

        if ($subscription->getStatus() !== SubscriptionStatus::CANCELLED || $subscription->getEndDate() <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
            return;
        }

        $user = $this->security->getUser();
        assert($user instanceof User);

        $content = $response->getContent();

        $session = $request->getSession();

        if ($session->has('checkout_url')) {
            $checkoutUrl = $session->get('checkout_url');
        } else {
            $checkoutUrl = $this->subscriptionManager->getCheckoutUrl($subscription, ['email' => $user->getEmail()]);
            $session->set('checkout_url', $checkoutUrl);
        }

        $message = '<strong>Subscription Canceled</strong> - Your subscription has been canceled. Your access will be revoked on ' . $subscription->getEndDate()->format('Y-m-d H:i:s') . '.<br /><a href="' . $checkoutUrl . '" class="btn btn-default btn-sm">Re-new now<a/> to avoid losing access.';

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
