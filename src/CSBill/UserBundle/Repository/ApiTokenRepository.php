<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Repository;

use CSBill\UserBundle\Entity\User;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiTokenRepository extends EntityRepository
{
    /**
     * Searches for a user by username or email.
     *
     * @param string $token
     *
     * @return UserInterface
     *
     * @throws UsernameNotFoundException
     */
    public function getUsernameForToken($token)
    {
        $q = $this
            ->createQueryBuilder('t')
            ->select('u.username')
            ->join('t.user', 'u')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleScalarResult();
        } catch (NoResultException $e) {
            return;
        }
    }

    /**
     * @param User $user
     *
     * @return array
     */
    public function getApiTokensForUser(User $user)
    {
        $qb = $this->createQueryBuilder('t');

        $hqb = $this->getEntityManager()
            ->getRepository('CSBillUserBundle:ApiTokenHistory')
            ->createQueryBuilder('th');

        $hqb->select($qb->expr()->max('th.created'))
            ->where('th.token = t')
            ->setMaxResults(1);

        $qb->select('t', 'h')
            ->leftJoin(
                't.history',
                'h',
                Join::WITH,
                $qb->expr()->eq('h.created', '('.$hqb->getDQL().')')
            )
            ->where('t.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}
