<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUserCount(): int
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('COUNT(u.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Searches for a user by username or email.
     *
     * @param string $username
     *
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username): UserInterface
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('(u.username = :username OR u.email = :email)')
            ->andWhere('u.enabled = 1')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    public function getGridQuery()
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id', 'u.username', 'u.email', 'u.enabled', 'u.created')
            ->groupBy('u.id');

        return $qb;
    }

    public function save(UserInterface $user)
    {
        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();
    }

    public function deleteUsers(array $users)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->delete()
            ->where($qb->expr()->in('u.id', $users));

        return $qb->getQuery()
            ->execute();
    }

    public function clearUserConfirmationToken(User $user)
    {
        $user->setConfirmationToken(null)
            ->setPasswordRequestedAt(null);

        $this->save($user);
    }
}
