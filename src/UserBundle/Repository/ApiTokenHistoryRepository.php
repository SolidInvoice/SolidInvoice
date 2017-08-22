<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Repository;

use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use Doctrine\ORM\EntityRepository;

class ApiTokenHistoryRepository extends EntityRepository
{
    /**
     * @param ApiTokenHistory $history
     * @param string          $token
     *
     * @return mixed|null
     */
    public function addHistory(ApiTokenHistory $history, string $token)
    {
        $entityManager = $this->getEntityManager();

        /** @var ApiToken $apiToken */
        $apiToken = $entityManager
            ->getRepository('SolidInvoiceUserBundle:ApiToken')
            ->findOneBy(['token' => $token]);

        $apiToken->addHistory($history);

        $entityManager->persist($history);
        $entityManager->flush();

        // delete the history for all but the last 100 records for each api token
        // This is to ensure the database doesn't grow to an unmanageable size
        $tableName = $this->getClassMetadata()->getTableName();
        $statement = $this->getEntityManager()
            ->getConnection()
            ->prepare("
                  DELETE FROM ${tablename}
                  WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id
                        FROM ${tablename}
                        WHERE token_id = ?
                        ORDER BY id DESC
                        LIMIT 100
                    ) as history
                )
                AND token_id = ?"
            );

        $id = $apiToken->getId();
        $statement->bindParam(1, $id);
        $statement->bindParam(2, $id);

        $statement->execute();
    }
}
