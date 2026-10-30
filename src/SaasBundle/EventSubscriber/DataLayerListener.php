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

use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_THROW_ON_ERROR;
use SolidInvoice\SaasBundle\Analytics\DataLayerQueue;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\SignupMethod;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use function json_encode;
use function str_contains;
use function str_replace;

/**
 * Pushes queued Google Tag Manager dataLayer events into the next HTML page,
 * and queues a GA4 `sign_up` event (with the signup method) the first time a
 * new user lands on onboarding. Every self-registered user (form, Google,
 * One Tap) passes through onboarding; invited users skip it by design.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Functional\DataLayerEventsTest
 */
final readonly class DataLayerListener
{
    private const string SIGN_UP_SENT = 'saas.data_layer_sign_up_sent';

    public function __construct(
        private DataLayerQueue $queue,
        private Security $security,
    ) {
    }

    #[AsEventListener(event: ResponseEvent::class)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $event->isMainRequest()
            || ! $request->isMethod('GET')
            || $response->getStatusCode() !== Response::HTTP_OK
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || ! $request->hasPreviousSession()
        ) {
            return;
        }

        $session = $request->getSession();
        $user = $this->security->getUser();

        // ponytail: once per session, so a user who abandons onboarding and returns
        // in a new session is counted twice. Move to a user setting if that skews GA.
        if ($request->attributes->get('_route') === '_onboarding'
            && $user instanceof User
            && $user->getSignupMethod() instanceof SignupMethod
            && ! $session->get(self::SIGN_UP_SENT, false)
        ) {
            $this->queue->push(['event' => 'sign_up', 'method' => $user->getSignupMethod()->value]);
            $session->set(self::SIGN_UP_SENT, true);
        }

        $events = $this->queue->pull();
        $content = $response->getContent();

        if ($events === [] || $content === false || ! str_contains($content, '</head>')) {
            return;
        }

        $script = '<script>window.dataLayer = window.dataLayer || [];';
        foreach ($events as $item) {
            $script .= 'window.dataLayer.push(' . json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) . ');';
        }

        $script .= '</script>';

        $response->setContent(str_replace('</head>', $script . '</head>', $content));
    }
}
