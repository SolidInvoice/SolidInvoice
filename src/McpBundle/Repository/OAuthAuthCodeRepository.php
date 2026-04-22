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

namespace SolidInvoice\McpBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use LogicException;
use SolidInvoice\McpBundle\Entity\OAuthAuthCode;
use SolidInvoice\McpBundle\OAuth\PendingAuthorization;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<OAuthAuthCode>
 */
final class OAuthAuthCodeRepository extends EntityRepository implements AuthCodeRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly PendingAuthorization $pendingAuthorization,
    ) {
        parent::__construct($registry, OAuthAuthCode::class);
    }

    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new OAuthAuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        if (! $authCodeEntity instanceof OAuthAuthCode) {
            throw new \InvalidArgumentException('Expected OAuthAuthCode instance.');
        }

        $user = $this->pendingAuthorization->getUser();
        $company = $this->pendingAuthorization->getCompany();

        if ($user === null || $company === null) {
            throw new LogicException('Cannot persist auth code without pending user/company binding.');
        }

        $authCodeEntity->setUser($user);
        $authCodeEntity->setCompany($company);

        if ($this->findOneBy(['identifier' => $authCodeEntity->getIdentifier()]) !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->save($authCodeEntity);
    }

    public function revokeAuthCode(string $codeId): void
    {
        $code = $this->findOneBy(['identifier' => $codeId]);

        if ($code instanceof OAuthAuthCode) {
            $code->revoke();
            $this->save($code);
        }
    }

    public function isAuthCodeRevoked(string $codeId): bool
    {
        $code = $this->findOneBy(['identifier' => $codeId]);

        if (! $code instanceof OAuthAuthCode) {
            return true;
        }

        return $code->isRevoked();
    }

    public function findByIdentifier(string $identifier): ?OAuthAuthCode
    {
        return $this->findOneBy(['identifier' => $identifier]);
    }
}
