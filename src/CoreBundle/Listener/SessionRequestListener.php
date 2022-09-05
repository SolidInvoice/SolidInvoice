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

namespace SolidInvoice\CoreBundle\Listener;

use SolidInvoice\CoreBundle\Response\FlashResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionRequestListener implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var string
     */
    protected $secret;

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function __construct(SessionInterface $session, string $secret)
    {
        $this->session = $session;
        $this->secret = $secret;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (! $event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response instanceof FlashResponse) {
            $flashBag = $this->session->getFlashBag();
            foreach ($response->getFlash() as $type => $message) {
                // Default to info for undefined types
                $flashBag->add($type, $message);
            }
        }
    }
}
