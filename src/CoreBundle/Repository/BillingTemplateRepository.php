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

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Entity\Company;
use Throwable;
use function strtolower;

/**
 * @extends ServiceEntityRepository<BillingTemplate>
 */
class BillingTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BillingTemplate::class);
    }

    public function findActive(string $type, string $variant): ?BillingTemplate
    {
        return $this->findOneBy([
            'type' => strtolower($type),
            'variant' => strtolower($variant),
            'active' => true,
        ]);
    }

    /**
     * @return list<BillingTemplate>
     */
    public function findAllForVariant(string $type, string $variant): array
    {
        /** @var list<BillingTemplate> $result */
        $result = $this->createQueryBuilder('bt')
            ->andWhere('bt.type = :type')
            ->andWhere('bt.variant = :variant')
            ->setParameter('type', strtolower($type))
            ->setParameter('variant', strtolower($variant))
            ->orderBy('bt.system', 'DESC')
            ->addOrderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findSystemTemplate(string $type, string $variant): ?BillingTemplate
    {
        return $this->findOneBy([
            'type' => strtolower($type),
            'variant' => strtolower($variant),
            'system' => true,
        ]);
    }

    public function save(BillingTemplate $template): void
    {
        $this->getEntityManager()->persist($template);
        $this->getEntityManager()->flush();
    }

    /**
     * Atomically flip the active flag so that only the given template is active
     * for its (company, type, variant) tuple. Other rows for the same tuple are
     * marked inactive in the same transaction.
     *
     * @throws Throwable
     */
    public function setActive(BillingTemplate $template): void
    {
        $em = $this->getEntityManager();

        $em->wrapInTransaction(function () use ($template): void {
            $this->createQueryBuilder('bt')
                ->update()
                ->set('bt.active', ':inactive')
                ->where('bt.type = :type')
                ->andWhere('bt.variant = :variant')
                ->setParameter('inactive', false)
                ->setParameter('type', $template->getType())
                ->setParameter('variant', $template->getVariant())
                ->getQuery()
                ->execute();

            $template->setActive(true);
            $this->getEntityManager()->persist($template);
            $this->getEntityManager()->flush();
        });
    }

    public function delete(BillingTemplate $template): void
    {
        if ($template->isSystem() || $template->isActive()) {
            return;
        }

        $this->getEntityManager()->remove($template);
        $this->getEntityManager()->flush();
    }

    /**
     * @return list<BillingTemplate>
     */
    public function findAllForCompany(Company $company): array
    {
        /** @var list<BillingTemplate> $result */
        $result = $this->createQueryBuilder('bt')
            ->orderBy('bt.type', 'ASC')
            ->addOrderBy('bt.variant', 'ASC')
            ->addOrderBy('bt.system', 'DESC')
            ->addOrderBy('bt.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
