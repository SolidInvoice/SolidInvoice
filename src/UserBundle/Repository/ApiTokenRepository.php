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
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_column;
use function array_combine;
use function array_map;

class ApiTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiToken::class);
    }

    public function getUsernameForToken(string $token): ?string
    {
        $q = $this
            ->createQueryBuilder('t')
            ->select('u.email')
            ->join('t.user', 'u')
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @return array{id: mixed, name: mixed, ip: mixed, token: mixed, lastUsed: mixed}
     */
    public function getApiTokensForUser(UserInterface $user): array
    {
        assert($user instanceof User);

        $tokens = $this->createQueryBuilder('t')
            ->select('t.id', 't.name', 't.token')
            ->where('t.user = :user')
            ->orderBy('t.created', 'DESC')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->getQuery()
            ->getArrayResult();

        $subQb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(h2.token) AS token_id', 'MAX(h2.created) AS max_created', 'h2.ip')
            ->from(ApiTokenHistory::class, 'h2')
            ->groupBy('h2.token');

        $history = $subQb->getQuery()->getArrayResult();

        $historyMap = array_combine(array_column($history, 'token_id'), $history);

        return array_map(static fn (array $token) => [
            'id' => $token['id'],
            'name' => $token['name'],
            'ip' => $historyMap[$token['id']->toBinary()]['ip'] ?? null,
            'token' => $token['token'],
            'lastUsed' => $historyMap[$token['id']->toBinary()]['max_created'] ?? null,
        ], $tokens);
    }

    public function revoke(ApiToken $token): void
    {
        $em = $this->getEntityManager();

        $em->remove($token);
        $em->flush();
    }
}
