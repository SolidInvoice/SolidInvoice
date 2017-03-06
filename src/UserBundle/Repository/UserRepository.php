<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserRepository extends EntityRepository implements UserProviderInterface
{
    /**
     * @return int
     */
    public function getUserCount()
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
     * @return UserInterface
     *
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
	$q = $this
	    ->createQueryBuilder('u')
		->select('u, r')
		    ->leftJoin('u.roles', 'r')
		->where('u.username = :username OR u.email = :email')
		->setParameter('username', $username)
		->setParameter('email', $username)
	    ->getQuery();

	try {
	    // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
	    $user = $q->getSingleResult();
	} catch (NoResultException $e) {
	    throw new UsernameNotFoundException(sprintf('User "%s" does not exist.', $username), 0, $e);
	}

	return $user;
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
}
