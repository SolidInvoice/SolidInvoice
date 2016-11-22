<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Behat;

use Behat\Gherkin\Node\TableNode;
use CSBill\CoreBundle\Behat\DefaultContext;
use CSBill\UserBundle\Entity\User;

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

        $encoderFactory = $container->get('security.encoder_factory');

        $entityManager = $container->get('doctrine')->getManager();

        foreach ($table as $data) {
            $user = new User();

            $encoder = $encoderFactory->getEncoder($user);

            $password = $encoder->encodePassword($data['password'], null);

            $user->setUsername($data['username'])
                ->setEmail($data['username'].'@local.dev')
                ->setPassword($password)
                ->setEnabled(true)
                ->setRoles(explode(',', $data['roles']));

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
        $userRepository = $entityManager->getRepository('CSBillUserBundle:User');

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
