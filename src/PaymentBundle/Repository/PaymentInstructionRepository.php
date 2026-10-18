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

namespace SolidInvoice\PaymentBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\PaymentBundle\Entity\PaymentInstruction;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<PaymentInstruction>
 * @see \SolidInvoice\PaymentBundle\Tests\Repository\PaymentInstructionRepositoryTest
 */
final class PaymentInstructionRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentInstruction::class);
    }

    /**
     * Returns a company's payment instructions, default first.
     *
     * @return list<PaymentInstruction>
     */
    public function findCompanyInstructions(Ulid $companyId): array
    {
        return $this->createQueryBuilder('pi')
            ->andWhere('pi.company = :company')
            ->setParameter('company', $companyId, UlidType::NAME)
            ->orderBy('pi.default', 'DESC')
            ->addOrderBy('pi.label', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
