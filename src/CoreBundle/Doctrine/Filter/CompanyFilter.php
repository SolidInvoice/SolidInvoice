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

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\UserBundle\Entity\User;
use function count;
use function str_replace;

class CompanyFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (User::class === $targetEntity->getName() && $this->hasParameter('companyId')) {
            try {
                $users = $this
                    ->getConnection()
                    ->createQueryBuilder()
                    ->select('user_id', 'company_id')
                    ->from('user_company')
                    ->where('company_id = :company')
                    ->setParameter(
                        'company',
                        Type::getType('uuid_binary_ordered_time')
                            ->convertToDatabaseValue(
                                str_replace("'", '', $this->getParameter('companyId')),
                                $this->getConnection()->getDatabasePlatform()
                            )
                    )
                    ->fetchAllAssociative();
            } catch (Exception $e) {
                return '';
            }

            if (count($users) > 0) {
                return sprintf('%s.id IN (%s)', $targetTableAlias, implode(',', array_column($users, 'user_id')));
            }

            return '';
        }

        if (! $targetEntity->hasAssociation('company')) {
            return '';
        }

        if ($this->hasParameter('companyId')) {
            $companyId = Type::getType('uuid_binary_ordered_time')
                ->convertToDatabaseValue(
                    str_replace("'", '', $this->getParameter('companyId')),
                    $this->getConnection()->getDatabasePlatform()
                );

            return sprintf('%s.company_id = "%s"', $targetTableAlias, $companyId);
        }

        return '';
    }
}
