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

namespace SolidInvoice\CoreBundle\Doctrine\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Makes the signed-in user the owner of every company they create, whichever way it is created
 * (web, onboarding, API, MCP). An owner the caller set is kept; with nobody signed in (a console
 * command, a fixture) the company has no owner.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Listener\CompanyOwnerListenerTest
 */
#[AsEntityListener(Events::prePersist, entity: Company::class)]
final readonly class CompanyOwnerListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function prePersist(Company $company): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        if (! $company->getOwner() instanceof User && $user instanceof User) {
            $company->setOwner($user);
        }
    }
}
