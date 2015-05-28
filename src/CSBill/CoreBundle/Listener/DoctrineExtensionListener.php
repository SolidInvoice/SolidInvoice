<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class DoctrineExtensionListener extends ContainerAware
{
    /**
     * @param GetResponseEvent $event
     */
    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $translatable = $this->container->get('gedmo.listener.translatable');
        $translatable->setTranslatableLocale($event->getRequest()->getLocale());
    }

    /**
     * Set the username on all loggable entities.
     */
    public function onKernelRequest()
    {
        $securityStorage = $this->container->get('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $securityChecker = $this->container->get('security.authorization_checker', ContainerInterface::NULL_ON_INVALID_REFERENCE);

        if (
            null !== $securityStorage &&
            null !== $securityChecker &&
            null !== ($token = $securityStorage->getToken()) &&
            $securityChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $loggable = $this->container->get('gedmo.listener.loggable');
            $loggable->setUsername($token->getUsername());
        }
    }
}
