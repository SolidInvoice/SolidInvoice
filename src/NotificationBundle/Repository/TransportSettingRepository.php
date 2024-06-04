<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;

/**
 * @extends ServiceEntityRepository<TransportSetting>
 */
final class TransportSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportSetting::class);
    }

    public function save(TransportSetting $setting): void
    {
        $this->_em->persist($setting);
        $this->_em->flush();
    }
}
