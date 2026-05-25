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

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Credit;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use function assert;

/**
 * @extends EntityRepository<Credit>
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
    public function addCredit(Client $client, BigNumber|float|int|string $amount): Credit
    {
        $credit = $client->getCredit();

        $value = $credit->getValue();
        assert($value instanceof BigInteger || $value instanceof BigDecimal);

        $credit->setValue($value->plus($amount));

        $this->save($credit);

        return $credit;
    }

    /**
     * @throws MathException
     */
    public function deductCredit(Client $client, BigNumber|float|int|string $amount): Credit
    {
        $credit = $client->getCredit();

        $value = $credit->getValue();
        assert($value instanceof BigInteger || $value instanceof BigDecimal);

        $credit->setValue($value->minus($amount));

        $this->save($credit);

        return $credit;
    }
}
