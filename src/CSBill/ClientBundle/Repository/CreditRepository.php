<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use Doctrine\ORM\EntityRepository;

class CreditRepository extends EntityRepository
{
    /**
     * @param Client $client
     * @param float  $amount
     */
    public function addCredit(Client $client, $amount)
    {
        $credit = $client->getCredit();

        $credit->setValue($credit->getValue() + $amount);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($credit);
        $entityManager->flush();
    }
}
