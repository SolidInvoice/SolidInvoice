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

namespace SolidInvoice\SettingsBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Throwable;

class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * @throws InvalidArgumentException|Throwable
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
                        ->setParameter('val', empty($value) ? null : $value)
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
