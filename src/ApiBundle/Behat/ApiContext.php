<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behatch\Context\RestContext;

use CSBill\UserBundle\Entity\ApiToken;
use CSBill\UserBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApiContext implements Context, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @BeforeScenario @api
     */
    public function setApiToken(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        /** @var RestContext $restContext */
        $restContext = $environment->getContext(RestContext::class);
        $restContext->iAddHeaderEqualTo("Content-Type", "application/ld+json");
        $restContext->iAddHeaderEqualTo("Accept", "application/ld+json");
        $restContext->iAddHeaderEqualTo("X-API-TOKEN", $this->ensureUserExists());
    }

    private function ensureUserExists(): string
    {
        $doctrine = $this->container->get('doctrine');

        $userRepository = $doctrine->getRepository('CSBillUserBundle:User');
        $tokenManager = $this->container->get('api.token.manager');

        $setToken = function ($tokenManager, $user, $doctrine): string {
            $token = $tokenManager->generateToken();
            $user->setApiTokens(new ArrayCollection([(new ApiToken())->setName('behat')->setToken($token)->setUser($user)]));
            $em = $doctrine->getManager();
            $em->persist($user);
            $em->flush();

            return $token;
        };

        /** @var User[] $users */
        if (count($users = $userRepository->findAll())) {
            $user = $users[0];
            if ($user->getApiTokens()->count() > 0) {
                return $user->getApiTokens()[0]->getToken();
            }
        } else {
            $fos = $this->container->get('fos_user.user_manager');

            $user = $fos->createUser();

            $user->setUsername('admin')
                ->setEmail('test@test.com')
                ->setPlainPassword('passwword')
                ->setEnabled(true)
                ->setSuperAdmin(true);

            $fos->updateUser($user);
        }

        return $setToken($tokenManager, $user, $doctrine);
    }
}