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

namespace SolidInvoice\UserBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use function assert;
use function sprintf;

/**
 * @see \SolidInvoice\UserBundle\Tests\Repository\UserRepositoryTest
 *
 * @extends ServiceEntityRepository<User>
 */
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

        try {
            return (int) $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException|Exception) {
            return 0;
        }
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('(u.username = :username OR u.email = :email)')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->setParameter('enabled', true)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            throw new UserNotFoundException(sprintf('User "%s" does not exist.', $username), 0, $e);
        }
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $class = $user::class;
        if (! $this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $class));
        }

        assert($user instanceof User);

        return $this->loadUserByIdentifier($user->getEmail());
    }

    public function supportsClass(string $class): bool
    {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb->select('u.id', 'u.username', 'u.email', 'u.enabled', 'u.created')
            ->groupBy('u.id');

        return $qb;
    }

    public function save(UserInterface $user): void
    {
        $em = $this->getEntityManager();

        $em->persist($user);
        $em->flush();
    }

    public function clearUserConfirmationToken(User $user): void
    {
        $user->setConfirmationToken(null)
            ->setPasswordRequestedAt(null);

        $this->save($user);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $q = $this
            ->createQueryBuilder('u')
            ->select('u')
            ->where('(u.email = :email)')
            ->andWhere('u.enabled = :enabled')
            ->setParameter('email', $identifier)
            ->setParameter('enabled', true)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleResult();
        } catch (NoResultException|NonUniqueResultException $e) {
            throw new UserNotFoundException(sprintf('User "%s" does not exist.', $identifier), 0, $e);
        }
    }
}
