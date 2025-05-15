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

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Uid\Ulid;
use Twig\Environment;

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
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequest',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (in_array($event->getRequest()->attributes->get('_route'), self::SKIPPED_ROUTES, true)) {
            return;
        }

        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            return;
        }

        $company = $this->companyRepository->find($companyId);

        if (! $company) {
            return;
        }

        $subscription = $this->subscriptionManager->getSubscriptionFor($company);

        if ($subscription?->getStatus() === SubscriptionStatus::PENDING) {
            $checkoutUrl = $this->subscriptionManager->getCheckoutUrl($subscription);
            $event->setResponse(
                new Response(
                    $this->twig->render('@SolidInvoiceSaas/subscription/pending.html.twig', [
                        'subscription' => $subscription,
                        'checkoutUrl' => $checkoutUrl,
                    ]),
                )
            );
        }
    }
}
