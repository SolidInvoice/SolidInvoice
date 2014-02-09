<?php

namespace CSBill\CoreBundle\Listener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use CSBill\CoreBundle\Security\Encryption;

class SessionRequestListener
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
     * @param SessionInterface $session
     * @param Encryption $encryption
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
