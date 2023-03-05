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

namespace SolidInvoice\CoreBundle\Company;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CompanySelector
{
    private SessionInterface $session;

    private ManagerRegistry $registry;

    public function __construct(SessionInterface $session, ManagerRegistry $registry)
    {
        $this->session = $session;
        $this->registry = $registry;
    }

    public function getCompany(): ?UuidInterface
    {
        return $this->session->get('companyId');
    }

    public function switchCompany(UuidInterface $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', $companyId->toString(), Types::STRING);

        $this->session->set('companyId', $companyId);
    }
}