<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class SectionRepository.
 */
class SectionRepository extends EntityRepository
{
    /**
     * Returns an array of all the top-level sections.
     *
     * @param bool   $cache
     * @param string $cacheKey
     * @param int    $lifetime
     *
     * @return array
     */
    public function getTopLevelSections(bool $cache = false, $cacheKey = 'csbill_settings_top_section_sections', int $lifetime = 604800): array
    {
        $qb = $this->createQueryBuilder('s')
                   ->where('s.parent IS NULL');

        $query = $qb->getQuery();

        if ($cache) {
            $query->useResultCache(true, $lifetime, $cacheKey);
        }

        return $query->getResult();
    }
}
