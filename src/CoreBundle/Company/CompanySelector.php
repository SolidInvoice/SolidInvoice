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

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Service\ResetInterface;
use function assert;
use function strtoupper;
use function substr;

final class CompanySelector implements ResetInterface
{
    private ?Ulid $companyId = null;

    public function __construct(
        private readonly ManagerRegistry $registry
    ) {
    }

    public function getCompany(): ?Ulid
    {
        return $this->companyId;
    }

    public function switchCompany(Ulid $companyId): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $isSqlite = $em->getConnection()->getDatabasePlatform() instanceof SqlitePlatform;

        $parameters = $isSqlite ?
            [
                strtoupper(substr($companyId->toHex(), 2)),
                'string',
            ] :
            [
                $companyId,
                UlidType::NAME,
            ];

        $em
            ->getFilters()
            ->enable('company')
            ->setParameter('companyId', ...$parameters);

        $this->companyId = $companyId;
    }

    public function reset(): void
    {
        $em = $this->registry->getManager();

        assert($em instanceof EntityManagerInterface);

        $em
            ->getFilters()
            ->disable('company');

        $this->companyId = null;
    }
}
