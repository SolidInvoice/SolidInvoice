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

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ApiBundle\Security\ApiTokenHasher;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;
use function array_column;
use function array_combine;
use function array_map;

/**
 * @extends EntityRepository<ApiToken>
 */
class ApiTokenRepository extends EntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ApiTokenHasher $hasher,
    ) {
        parent::__construct($registry, ApiToken::class);
    }

    /**
     * Looks up the username for a given plaintext API token. The token is
     * hashed before the query so the database only ever sees the hash.
     */
    public function getUsernameForToken(string $plaintextToken): ?string
    {
        $q = $this
            ->createQueryBuilder('t')
            ->select('u.email')
            ->join('t.user', 'u')
            ->where('t.token = :token')
            ->setParameter('token', $this->hasher->hash($plaintextToken))
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception if there is no record matching the criteria.
            return $q->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }

    /**
     * Finds an ApiToken entity by its plaintext value. Returns null when no
     * matching token exists.
     */
    public function findOneByPlaintext(string $plaintextToken): ?ApiToken
    {
        return $this->findOneBy(['token' => $this->hasher->hash($plaintextToken)]);
    }

    /**
     * @return list<array{id: Ulid, name: string, ip: string|null, lastUsed: DateTimeInterface|null}>
     */
    public function getApiTokensForUser(UserInterface $user): array
    {
        assert($user instanceof User);

        /** @var list<array{id: Ulid, name: string}> $tokens */
        $tokens = $this->createQueryBuilder('t')
            ->select('t.id', 't.name')
            ->where('t.user = :user')
            ->orderBy('t.created', 'DESC')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->getQuery()
            ->getArrayResult();

        $subQb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(h2.token) AS token_id', 'MAX(h2.created) AS max_created', 'h2.ip')
            ->from(ApiTokenHistory::class, 'h2')
            ->groupBy('h2.token', 'h2.ip');

        /** @var list<array{token_id: string, max_created: DateTimeInterface, ip: string}> $history */
        $history = $subQb->getQuery()->getArrayResult();

        $historyMap = array_combine(array_column($history, 'token_id'), $history);

        return array_map(static fn (array $token) => [
            'id' => $token['id'],
            'name' => $token['name'],
            'ip' => $historyMap[$token['id']->toBinary()]['ip'] ?? null,
            'lastUsed' => $historyMap[$token['id']->toBinary()]['max_created'] ?? null,
        ], $tokens);
    }

    public function revoke(ApiToken $token): void
    {
        $em = $this->getEntityManager();

        $em->remove($token);
        $em->flush();
    }

    public function getActiveTokenCountForUser(UserInterface $user): int
    {
        assert($user instanceof User);

        try {
            return (int) $this->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.user = :user')
                ->setParameter('user', $user->getId(), UlidType::NAME)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    public function getApiCallsThisMonthForUser(UserInterface $user): int
    {
        assert($user instanceof User);

        $firstDayOfMonth = CarbonImmutable::parse('first day of this month 00:00:00');

        try {
            return (int) $this->createQueryBuilder('t')
                ->select('COUNT(h.id)')
                ->innerJoin('t.history', 'h')
                ->where('t.user = :user')
                ->andWhere('h.created >= :firstDay')
                ->setParameter('user', $user->getId(), UlidType::NAME)
                ->setParameter('firstDay', $firstDayOfMonth)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    public function getLastActivityForUser(UserInterface $user): ?DateTimeInterface
    {
        assert($user instanceof User);

        try {
            $result = $this->createQueryBuilder('t')
                ->select('h.created')
                ->innerJoin('t.history', 'h')
                ->where('t.user = :user')
                ->setParameter('user', $user->getId(), UlidType::NAME)
                ->orderBy('h.created', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();

            if (null === $result) {
                return null;
            }

            return $result instanceof DateTimeInterface ? $result : null;
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }

    public function getMostUsedTokenForUser(UserInterface $user): ?string
    {
        assert($user instanceof User);

        try {
            return $this->createQueryBuilder('t')
                ->select('t.name')
                ->innerJoin('t.history', 'h')
                ->where('t.user = :user')
                ->groupBy('t.id')
                ->orderBy('COUNT(h.id)', 'DESC')
                ->setMaxResults(1)
                ->setParameter('user', $user->getId(), UlidType::NAME)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return null;
        }
    }
}
