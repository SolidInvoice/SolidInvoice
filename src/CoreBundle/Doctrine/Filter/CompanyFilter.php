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
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Filter\CompanyFilterTest
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
