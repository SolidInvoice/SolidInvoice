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

use CSBill\CoreBundle\Util\ArrayUtil;
use Doctrine\ORM\EntityRepository;

/**
 * Class SettingsRepository.
 */
class SettingsRepository extends EntityRepository
{
    /**
     * Gets section specific settings.
     *
     * @param string $section
     * @param bool   $combineArray Should the settings be returned as a key => value array
     *
     * @return array
     */
    public function getSettingsBySection($section, $combineArray = true)
    {
        $qb = $this->createQueryBuilder('s')
                    ->where('s.section = :section')
                    ->orderBy('s.key', 'ASC')
                    ->setParameter('section', $section);

        $query = $qb->getQuery()
                    ->useQueryCache(true);

        $result = $query->getResult();

        if (count($result) > 0) {
            if ($combineArray) {
                return array_combine(ArrayUtil::column($result, 'key'), ArrayUtil::column($result, 'value'));
            }
        }

        return $result;
    }
}
