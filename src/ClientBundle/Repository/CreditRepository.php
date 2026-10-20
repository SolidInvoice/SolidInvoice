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

namespace SolidInvoice\ClientBundle\Repository;

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use RuntimeException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidInvoice\ClientBundle\Exception\InsufficientCreditException;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<Credit>
 * @see \SolidInvoice\ClientBundle\Tests\Repository\CreditRepositoryTest
 */
class CreditRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Credit::class);
    }

    /**
     * @throws MathException
     */
    public function addCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $intAmount = $this->toIntAmount($amount);

        if ($intAmount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        $affected = $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Credit::class, 'c')
            ->set('c.value', 'c.value + :amount')
            ->set('c.updated', ':now')
            ->where('c.client = :clientId')
            ->setParameter('amount', $intAmount, Types::BIGINT)
            ->setParameter('clientId', $client->getId(), UlidType::NAME)
            ->setParameter('now', CarbonImmutable::now(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();

        if ((int) $affected === 0) {
            throw new RuntimeException('No credit record found for client; credit was not added.');
        }

        $credit = $client->getCredit();
        $this->getEntityManager()->refresh($credit);

        return $credit;
    }

    /**
     * @throws MathException
     * @throws InsufficientCreditException
     */
    public function deductCredit(Client $client, BigNumber | float | int | string $amount): Credit
    {
        $intAmount = $this->toIntAmount($amount);

        if ($intAmount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }

        // Single atomic UPDATE: checks balance and deducts in one statement.
        // If the WHERE clause fails (insufficient credit), zero rows are affected.
        $affected = $this->getEntityManager()
            ->createQueryBuilder()
            ->update(Credit::class, 'c')
            ->set('c.value', 'c.value - :amount')
            ->set('c.updated', ':now')
            ->where('c.client = :clientId')
            ->andWhere('c.value >= :amount')
            ->setParameter('amount', $intAmount, Types::BIGINT)
            ->setParameter('clientId', $client->getId(), UlidType::NAME)
            ->setParameter('now', CarbonImmutable::now(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();

        if ((int) $affected === 0) {
            throw new InsufficientCreditException();
        }

        $credit = $client->getCredit();
        $this->getEntityManager()->refresh($credit);

        return $credit;
    }

    /**
     * @throws MathException
     */
    private function toIntAmount(BigNumber | float | int | string $amount): int
    {
        if (is_float($amount)) {
            $amount = (string) $amount;
        }

        return BigNumber::of($amount)->toScale(0, RoundingMode::Unnecessary)->toInt();
    }
}
