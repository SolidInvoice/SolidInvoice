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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use SolidInvoice\CoreBundle\Contracts\EmailVerificationGateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final readonly class EmailVerificationBannerListener implements EventSubscriberInterface
{
    public function __construct(
        private EmailVerificationGateInterface $gate,
        private Environment $twig,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Priority -10 ensures this runs AFTER the trial banner listener,
        // so the verification banner is injected on top of the already-modified
        // content — landing it directly below the trial banner in the rendered DOM.
        return [
            ResponseEvent::class => ['onResponse', -10],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $request->isMethod('GET') || $response->getStatusCode() !== Response::HTTP_OK) {
            return;
        }

        if (! $this->gate->isGated()) {
            return;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }

        $banner = $this->twig->render('@SolidInvoiceSaas/_alert_banner.html.twig', [
            'type' => 'warning',
            'icon' => 'tabler:mail-exclamation',
            'title' => $this->translator->trans('email_verification.banner.title', [], 'messages'),
            'message' => $this->translator->trans('email_verification.banner.message', [], 'messages'),
        ]);

        $content = preg_replace(
            '/<div class="page-wrapper">/',
            '<div class="page-wrapper">' . $banner,
            $content,
            1,
        );

        $response->setContent($content);
    }
}
