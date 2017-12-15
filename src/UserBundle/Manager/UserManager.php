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

namespace SolidInvoice\UserBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\PasswordUpdaterInterface;

/**
 * Class UserManager.
 */
class UserManager extends BaseUserManager
{
    protected $objectManager;

    protected $class;

    protected $repository;

    private $entity;

    public function __construct(PasswordUpdaterInterface $passwordUpdater, CanonicalFieldsUpdater $canonicalFieldsUpdater, ObjectManager $om, $class)
    {
        parent::__construct($passwordUpdater, $canonicalFieldsUpdater);

        $this->objectManager = $om;
        $this->entity = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteUser(UserInterface $user)
    {
        $this->objectManager->remove($user);
        $this->objectManager->flush();
    }

    public function deleteUsers(array $users)
    {
        $repository = $this->objectManager->getRepository('SolidInvoiceUserBundle:User');

        $qb = $repository->createQueryBuilder('u');

        $qb->delete()
            ->where($qb->expr()->in('u.id', $users));

        return $qb->getQuery()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        if (null === $this->class) {
            $metadata = $this->objectManager->getClassMetadata($this->entity);
            $this->class = $metadata->getName();
        }

        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function findUserBy(array $criteria)
    {
        if (null === $this->repository) {
            $this->repository = $this->objectManager->getRepository($this->entity);
        }

        return $this->repository->findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     */
    public function findUsers()
    {
        if (null === $this->repository) {
            $this->repository = $this->objectManager->getRepository($this->entity);
        }

        return $this->repository->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function reloadUser(UserInterface $user)
    {
        $this->objectManager->refresh($user);
    }

    /**
     * Updates a user.
     *
     * @param UserInterface $user
     * @param bool          $flush Whether to flush the changes (default true)
     */
    public function updateUser(UserInterface $user, bool $flush = true)
    {
        $this->updateCanonicalFields($user);
        $this->updatePassword($user);

        $this->objectManager->persist($user);

        if ($flush) {
            $this->objectManager->flush();
        }
    }
}
