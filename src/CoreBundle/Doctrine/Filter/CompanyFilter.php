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

namespace SolidInvoice\CoreBundle\Doctrine\Filter;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\UserBundle\Entity\User;
use function sprintf;

/**
 * Scopes every company-aware entity to the active company.
 *
 * ---------------------------------------------------------------------------
 * `find()` applies this filter. It is not a cross-tenant hole.
 * ---------------------------------------------------------------------------
 *
 * This gets re-litigated often enough to be worth settling here, with line numbers rather
 * than adjectives. In the Doctrine ORM this repo has installed:
 *
 *  - `EntityRepository::find()` (vendor/doctrine/orm/src/EntityRepository.php:86) delegates to
 *    `EntityManager::find()` (vendor/doctrine/orm/src/EntityManager.php:274).
 *  - Missing the identity map, it calls `loadById()` -> `load()`
 *    (vendor/doctrine/orm/src/Persisters/Entity/BasicEntityPersister.php:754, then :725), which
 *    builds its SQL in `getSelectSQL()` (:1109). Line :1146 collects every enabled filter's
 *    constraint and :1149-1153 ANDs it into the WHERE clause. The filter is in the query.
 *  - `findOneBy()` (EntityRepository.php:125) calls that *same* `load()`, so it is filtered by
 *    the same line. `find()` and `findOneBy()` are not stronger or weaker than each other here.
 *
 * Every other read path reaches the same `getSelectSQL()` — `refresh()` (:851), `loadAll()`
 * (:884), `loadCriteria()` (:961), and the collection loads (:1103, :1869) — and DQL applies
 * filters in `SqlWalker::generateFilterConditionSQL()` (vendor/doctrine/orm/src/Query/SqlWalker.php:454).
 *
 * The one genuine difference: `EntityManager::find()` checks the identity map first
 * (EntityManager.php:325-347) and returns a hit without emitting SQL, so no filter runs.
 * `findOneBy()` has no such shortcut. That only matters where an entity can already be managed,
 * which in a normal request means it was loaded through a path the filter already allowed. It
 * bites in functional tests, which seed several tenants through one EntityManager, and it is the
 * reason to prefer `findOneBy(['id' => ...])` in code that may run with a warm EntityManager
 * (message handlers, worker-mode requests) — defence in depth, not a fix for a live hole.
 *
 * Before writing "find() bypasses the filter" in a comment or a commit message, read
 * {@see \SolidInvoice\CoreBundle\Tests\Functional\CompanyFilterLookupTest}, which proves all four
 * combinations of {find, findOneBy} x {warm, cold identity map} against a real database.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Filter\CompanyFilterTest
 * @see \SolidInvoice\CoreBundle\Tests\Functional\CompanyFilterLookupTest
 * @see \SolidInvoice\CoreBundle\Tests\Functional\AccountIsolationTest
 */
class CompanyFilter extends SQLFilter
{
    /**
     * @param ClassMetadata<object> $targetEntity
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        $platform = $this->getConnection()->getDatabasePlatform();

        // The company id is bound as an uppercase hex string rather than raw binary, since quoting
        // binary into a filter parameter is not portable (see CompanySelector::switchCompany()).
        //
        // Decode the literal instead of encoding the column wherever the platform allows it: wrapping
        // the column in HEX() makes the comparison non-sargable, which disqualifies every index on
        // company_id and forces a full table scan on every multi-tenant query.
        if ($platform instanceof PostgreSQLPlatform) {
            // Native uuid column, compared against the RFC 4122 representation.
            $column = static fn (string $column): string => $column;
            $value = fn (): string => $this->getParameter('companyId');
        } elseif ($platform instanceof AbstractMySQLPlatform) {
            // BINARY(16) column, and UNHEX() on the literal is folded to a constant.
            $column = static fn (string $column): string => $column;
            $value = fn (): string => sprintf('UNHEX(%s)', $this->getParameter('companyId'));
        } else {
            // SQLite has no hex decoder before 3.41, so fall back to encoding the column.
            $column = static fn (string $column): string => sprintf('HEX(%s)', $column);
            $value = fn (): string => $this->getParameter('companyId');
        }

        if (User::class === $targetEntity->getName() && $this->hasParameter('companyId')) {
            $query = $this
                ->getConnection()
                ->createQueryBuilder()
                ->select($column('user_id'))
                ->from('user_company')
                ->where(
                    sprintf(
                        '%s = %s',
                        $column('company_id'),
                        $value()
                    ),
                );

            return sprintf(
                $column('%s.id') . ' IN (%s)',
                $targetTableAlias,
                $query->getSQL()
            );
        }

        if (! $targetEntity->hasAssociation('company')) {
            return '';
        }

        if ($this->hasParameter('companyId')) {
            return sprintf($column('%s.company_id') . ' = %s', $targetTableAlias, $value());
        }

        return '';
    }
}
