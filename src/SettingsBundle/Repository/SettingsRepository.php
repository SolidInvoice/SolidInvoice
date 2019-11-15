<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SettingsBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use SolidInvoice\SettingsBundle\Entity\Setting;

class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * @param array $settings
     *
     * @throws \InvalidArgumentException|\Throwable
     */
    public function save(array $settings): void
    {
        try {
            $this->_em->transactional(function () use ($settings) {
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
            });
        } finally {
            // Clear the repository, to not keep previous setting values
            $this->clear();
        }
    }
}
