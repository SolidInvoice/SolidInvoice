<?php

namespace CSBill\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class ApiTokenRepository extends EntityRepository
{
    /**
     * Searches for a user by username or email
     *
     * @param  string $token
     *
     * @return UserInterface
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
            return null;
        }
    }
}
