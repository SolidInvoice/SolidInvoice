<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DoctrineExtensionListener implements ContainerAwareInterface, EventSubscriberInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest'],
                ['onLateKernelRequest', -10],
            ],
        ];
    }

    /**
     * Translatable sets locale after router processing.
     *
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
        /* @var TokenStorageInterface  $securityStorage */
        $securityStorage = $this->container->get('security.token_storage', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        /* @var AuthorizationCheckerInterface $securityChecker */
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
