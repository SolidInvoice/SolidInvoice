<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Listener;

use CSBill\CoreBundle\Security\Encryption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SessionRequestListener implements EventSubscriberInterface
{
    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var Encryption
     */
    protected $encryption;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 200],
        ];
    }

    /**
     * @param SessionInterface $session
     * @param Encryption       $encryption
     */
    public function __construct(SessionInterface $session, Encryption $encryption)
    {
        $this->session = $session;
        $this->encryption = $encryption;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->request->has('sessionId')) {
            $request->cookies->set($this->session->getName(), 1);

            $sessionId = $this->encryption->decrypt($request->request->get('sessionId'));

            $this->session->setId($sessionId);
        }
    }
}
