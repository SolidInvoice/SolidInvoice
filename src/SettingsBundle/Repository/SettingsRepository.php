<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Repository;

use Doctrine\ORM\EntityRepository;

class SettingsRepository extends EntityRepository
{
    /**
     * @param array $settings
     */
    public function save(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->createQueryBuilder('s')
                ->update()
                ->set('s.value', ':val')
                ->where('s.key = :key')
                ->setParameter('key', $key)
                ->setParameter('val', !empty($value) ? $value : null)
                ->getQuery()
                ->execute();
        }
    }
}
