<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionRequestListener implements EventSubscriberInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var string
     */
    protected $secret;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @param Session $session
     * @param string  $secret
     */
    public function __construct(Session $session, string $secret)
    {
        $this->session = $session;
        $this->secret = $secret;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->request->has('sessionId')) {
            $request->cookies->set($this->session->getName(), 1);

            $sessionId = Crypto::decrypt($request->request->get('sessionId'), Key::loadFromAsciiSafeString($this->secret));

            $this->session->setId($sessionId);
        }
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof FlashResponse) {
            $flashBag = $this->session->getFlashBag();
            foreach ($response->getFlash() as $type => $message) {
                // Default to info for undefined types
                $flashBag->add(is_int($type) ? FlashResponse::FLASH_INFO : $type, $message);
            }
        }
    }
}
