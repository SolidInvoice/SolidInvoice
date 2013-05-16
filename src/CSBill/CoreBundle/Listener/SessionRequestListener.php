<?php

namespace CSBill\CoreBundle\Listener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerAware;

class SessionRequestListener extends ContainerAware
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->request->has('sessionId')) {
            $request->cookies->set(session_name(), 1);

            $session = $this->container->get('session');

            $sessionId = $this->container->get('security.encryption')->decrypt($request->request->get('sessionId'));

            $session->setId($sessionId);
        }
    }
}
