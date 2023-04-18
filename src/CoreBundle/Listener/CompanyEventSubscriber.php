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

use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use function assert;
use function count;
use function in_array;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Listener\CompanyEventSubscriberTest
 */
final class CompanyEventSubscriber implements EventSubscriberInterface
{
    private CompanySelector $companySelector;

    private RouterInterface $router;

    private Security $security;

    public function __construct(
        RouterInterface $router,
        CompanySelector $companySelector,
        Security $security
    ) {
        $this->companySelector = $companySelector;
        $this->router = $router;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $session = $request->getSession();
        assert($session instanceof SessionInterface);

        if ($session->has('company')) {
            $this->companySelector->switchCompany($session->get('company'));
        } elseif (! $this->isOnCompanySelectionRoute($request) && ($user = $this->security->getUser()) instanceof UserInterface) {
            assert($user instanceof User);

            if (count($user->getCompanies()) === 1) {
                $this->companySelector->switchCompany($user->getCompanies()->first()->getId());
                $session->set('company', $user->getCompanies()->first()->getId());
                return;
            }

            $event->setResponse(new RedirectResponse($this->router->generate('_select_company')));
            $event->stopPropagation();
        }
    }

    private function isOnCompanySelectionRoute(Request $request): bool
    {
        $routeName = $request->attributes->get('_route');

        return in_array($routeName, ['_select_company', '_switch_company', '_create_company'], true);
    }
}
