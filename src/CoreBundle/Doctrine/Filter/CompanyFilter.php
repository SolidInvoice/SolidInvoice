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

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\UserBundle\Entity\User;
use function sprintf;

class CompanyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        $isSqlite = $this->getConnection()->getDatabasePlatform() instanceof SqlitePlatform;

        $encode = static function (string $value) use ($isSqlite): string {
            return $isSqlite ? sprintf('HEX(%s)', $value) : $value;
        };

        if (User::class === $targetEntity->getName() && $this->hasParameter('companyId')) {
            $query = $this
                ->getConnection()
                ->createQueryBuilder()
                ->select($encode('user_id'))
                ->from('user_company')
                ->where(
                    sprintf(
                        '%s = %s',
                        $encode('company_id'),
                        $this->getParameter('companyId')
                    ),
                );

            return sprintf(
                $encode('%s.id') . ' IN (%s)',
                $targetTableAlias,
                $query->getSQL()
            );
        }

        if (! $targetEntity->hasAssociation('company')) {
            return '';
        }

        if ($this->hasParameter('companyId')) {
            return sprintf($encode('%s.company_id') . ' = %s', $targetTableAlias, $this->getParameter('companyId'));
        }

        return '';
    }
}
