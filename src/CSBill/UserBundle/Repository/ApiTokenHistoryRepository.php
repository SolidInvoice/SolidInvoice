<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Repository;

use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Entity\ApiTokenHistory;
use Doctrine\ORM\EntityRepository;

class ApiTokenHistoryRepository extends EntityRepository
{
    /**
     * @param ApiTokenHistory $history
     * @param string          $token
     *
     * @return mixed|null
     */
    public function addHistory(ApiTokenHistory $history, $token)
    {
        $entityManager = $this->getEntityManager();

        /** @var ApiToken $apiToken */
        $apiToken = $entityManager
            ->getRepository('CSBillUserBundle:ApiToken')
            ->findOneBy(array('token' => $token));

        $apiToken->addHistory($history);

        $entityManager->persist($history);
        $entityManager->flush();

        // delete the history for all but the last 100 records for each api token
        // This is to ensure the database doesn't grow to an unmanageable size
        $tableName = $this->getClassMetadata()->getTableName();
        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare('
                  DELETE FROM '.$tableName.'
                  WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id
                        FROM '.$tableName.'
                        WHERE token_id = ?
                        ORDER BY id DESC
                        LIMIT 100
                    ) as history
                )
                AND token_id = ?'
            )
        ;

        $id = $apiToken->getId();
        $statement->bindParam(1, $id);
        $statement->bindParam(2, $id);

        $statement->execute();
    }
}
