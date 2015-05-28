<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
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
    public function getTopLevelSections($cache = false, $cacheKey = 'csbill_settings_top_section_sections', $lifetime = 604800)
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
