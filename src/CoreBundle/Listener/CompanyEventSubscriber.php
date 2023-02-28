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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

final class CompanyEventSubscriber implements EventSubscriberInterface
{
    private ManagerRegistry $doctrine;

    private CompanySelector $companySelector;

    private RouterInterface $router;

    private Security $security;

    public function __construct(
        ManagerRegistry $doctrine,
        RouterInterface $router,
        CompanySelector $companySelector,
        Security $security
    ) {
        $this->doctrine = $doctrine;
        $this->companySelector = $companySelector;
        $this->router = $router;
        $this->security = $security;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 120],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();

        $company = $this->companySelector->getCompany();

        if (
            null === $company &&
            '_select_company' !== $request->attributes->get('_route') &&
            '_switch_company' !== $request->attributes->get('_route') &&
            '_create_company' !== $request->attributes->get('_route') &&
            null !== $this->security->getUser()
        ) {
            $event->setResponse(new RedirectResponse($this->router->generate('_select_company')));
            $event->stopPropagation();

            return;
        }

        if (null !== $company) {
            $em->getFilters()
                ->enable('company')
                ->setParameter('companyId', $company->toString(), Types::STRING);
        }
    }
}
