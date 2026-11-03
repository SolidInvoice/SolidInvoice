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

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use SolidInvoice\CoreBundle\Entity\Company;
use function implode;
use function sprintf;

/**
 * Hides disabled companies, and everything that belongs to them, as if they did not exist.
 *
 * Kept apart from CompanyFilter on purpose: the cron commands disable that one to scan every
 * tenant, and this one must still apply while they do.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Doctrine\Filter\CompanyEnabledFilterTest
 */
final class CompanyEnabledFilter extends SQLFilter
{
    /**
     * @param ClassMetadata<object> $targetEntity
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        $platform = $this->getConnection()->getDatabasePlatform();

        if (Company::class === $targetEntity->getName()) {
            return sprintf('%s.enabled = %s', $targetTableAlias, $platform->convertBooleans(true));
        }

        $constraints = [];

        // By target class rather than a "company" field name, so Platform's Subscription (whose
        // subscriber resolves to Company) is covered too. NOT EXISTS rather than NOT IN, which
        // would drop rows with a NULL column the moment any company is disabled.
        foreach ($targetEntity->getAssociationMappings() as $fieldName => $association) {
            if (Company::class !== $association->targetEntity || ! $targetEntity->isAssociationWithSingleJoinColumn($fieldName)) {
                continue;
            }

            $constraints[] = sprintf(
                'NOT EXISTS (SELECT 1 FROM %s WHERE id = %s.%s AND enabled = %s)',
                Company::TABLE_NAME,
                $targetTableAlias,
                $targetEntity->getSingleAssociationJoinColumnName($fieldName),
                $platform->convertBooleans(false),
            );
        }

        return implode(' AND ', $constraints);
    }
}
