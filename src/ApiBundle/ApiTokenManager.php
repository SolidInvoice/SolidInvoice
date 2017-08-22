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

namespace SolidInvoice\ApiBundle;

use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;

class ApiTokenManager
{
    const TOKEN_LENGTH = 32;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * ApiTokenManager constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param User   $user
     * @param string $name
     *
     * @return ApiToken
     */
    public function getOrCreate(User $user, string $name): ApiToken
    {
        $tokens = $user->getApiTokens();

        /** @var ApiToken $token */
        foreach ($tokens as $token) {
            if ($token->getName() === $name) {
                return $token;
            }
        }

        return $this->create($user, $name);
    }

    /**
     * @param User   $user
     * @param string $name
     *
     * @return ApiToken
     */
    public function create(User $user, string $name): ApiToken
    {
        $apiToken = new ApiToken();

        $apiToken->setToken($this->generateToken());
        $apiToken->setUser($user);
        $apiToken->setName($name);

        $entityManager = $this->registry->getManager();

        $entityManager->persist($apiToken);
        $entityManager->flush();

        return $apiToken;
    }

    /**
     * @return string
     */
    public function generateToken(): string
    {
        return bin2hex(random_bytes(self::TOKEN_LENGTH));
    }
}
