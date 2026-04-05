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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/** @implements ProcessorInterface<ApiToken, ApiToken> */
final class ApiTokenCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ApiTokenManager $apiTokenManager,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ApiToken
    {
        assert($data instanceof ApiToken);

        $user = $this->security->getUser();

        if (! $user instanceof User) {
            throw new AccessDeniedHttpException('You must be authenticated to create an API token.');
        }

        $token = $this->apiTokenManager->create($user, $data->getName() ?? '');
        $token->setDescription($data->getDescription());
        $this->entityManager->flush();

        return $token;
    }
}
