<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CS\CoreBundle\Util\ArrayUtil;

/**
 * Class SettingsRepository
 * @package CSBill\SettingsBundle\Repository
 */
class SettingsRepository extends EntityRepository
{
    /**
     * Gets section specific settings
     *
     * @param  string $section
     * @param  bool   $combineArray Should the settings be returned as a key => value array
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
