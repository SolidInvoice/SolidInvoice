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

use Doctrine\Persistence\ManagerRegistry;
use LogicException;
use Payum\Core\Model\Identity;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\SecurityToken;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<Company>
 * @see \SolidInvoice\CoreBundle\Tests\Repository\CompanyRepositoryTest
 */
class CompanyRepository extends EntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CompanySelector $companySelector
    ) {
        parent::__construct($registry, Company::class);
    }

    public function updateCompanyName(string $value): void
    {
        $company = $this->companySelector->getCompany();

        if ($company instanceof Ulid) {
            $this->createQueryBuilder('c')
                ->update()
                ->set('c.name', ':name')
                ->where('c.id = :id')
                ->setParameter('name', $value)
                ->setParameter('id', $company, UlidType::NAME)
                ->getQuery()
                ->execute();
        }
    }

    public function updateCustomDomain(?string $value): ?string
    {
        $companyId = $this->companySelector->getCompany();

        if (! $companyId instanceof Ulid) {
            throw new LogicException('Cannot update custom domain without an active company context.');
        }

        $company = $this->find($companyId);

        if (! $company instanceof Company) {
            throw new LogicException('Cannot update custom domain: active company could not be loaded.');
        }

        $company->setCustomDomain($value);

        $this->getEntityManager()->flush();

        return $company->getCustomDomain();
    }

    public function findOneByCustomDomain(string $host): ?Company
    {
        $host = Company::normalizeCustomDomain($host);

        if ($host === null) {
            return null;
        }

        return $this->createQueryBuilder('c')
            ->where('c.customDomain = :host')
            ->setParameter('host', $host)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteCompany(?Ulid $companyId): void
    {
        if (! $companyId instanceof Ulid) {
            return;
        }

        $company = $this->find($companyId);

        if (! $company instanceof Company) {
            return;
        }

        $em = $this->getEntityManager();

        // Delete Payum security tokens for this company's payments.
        // security_token.details stores a PHP-serialized Identity object (no FK to payments),
        // so ORM cascade cannot reach these rows. Load the company's payments, then match
        // SecurityToken records by their deserialized Identity rather than raw SQL.
        $payments = $em->getRepository(Payment::class)->findBy(['company' => $companyId]);

        if ($payments !== []) {
            $paymentIds = [];
            foreach ($payments as $payment) {
                $paymentIds[(string) $payment->getId()] = true;
            }

            /** @var SecurityToken[] $tokens */
            $tokens = $em->getRepository(SecurityToken::class)->findAll();
            foreach ($tokens as $token) {
                $details = $token->getDetails();

                if (! $details instanceof Identity) {
                    continue;
                }

                if ($details->getClass() !== Payment::class) {
                    continue;
                }

                if (! isset($paymentIds[(string) $details->getId()])) {
                    continue;
                }

                $em->remove($token);
            }
        }

        $em->remove($company);
        $em->flush();
    }
}
