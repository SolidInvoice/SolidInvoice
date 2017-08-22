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

namespace SolidInvoice\UserBundle\Behat;

use Behat\Gherkin\Node\TableNode;
use SolidInvoice\CoreBundle\Behat\DefaultContext;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Manager\UserManager;

/**
 * @codeCoverageIgnore
 */
class UserContext extends DefaultContext
{
    /**
     * @Given /^I have the following users:$/
     *
     * @param TableNode $table
     *
     * @throws \Exception
     */
    public function usersExistsValues(TableNode $table)
    {
        $container = $this->getContainer();

        /** @var UserManager $fos */
        $fos = $container->get('fos_user.user_manager');

        $entityManager = $container->get('doctrine')->getManager();

        foreach ($table as $data) {
            $user = $fos->createUser();

            $user->setUsername($data['username'])
                ->setEmail($data['username'].'@local.dev')
                ->setPlainPassword($data['password'])
                ->setEnabled(true)
                ->setRoles(explode(',', $data['roles']));

            $fos->updateUser($user);

            $user->setConfirmationToken(null)
                ->setEnabled(true)
                ->setSuperAdmin(true);

            $entityManager->persist($user);
        }

        $entityManager->flush();
    }

    /**
     * @Then /^the following user must exist:$/
     *
     * @param TableNode $table
     *
     * @throws \Exception
     */
    public function userExists(TableNode $table)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getManager();
        $userRepository = $entityManager->getRepository('SolidInvoiceUserBundle:User');

        /** @var User[] $users */
        $users = $userRepository->findAll();

        foreach ($table->getHash() as $row) {
            $match = false;
            foreach ($users as $user) {
                if (
                    $user->getUsername() === $row['username'] &&
                    $user->getEmail() === $row['email'] &&
                    password_verify($row['password'], $user->getPassword())
                ) {
                    $match = true;

                    break;
                }
            }

            if (false === $match) {
                throw new \Exception(sprintf('User with username "%s" does not exist', $row['username']));
            }
        }
    }
}
