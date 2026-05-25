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

namespace SolidInvoice\NotificationBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<TransportSetting>
 */
final class TransportSettingRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransportSetting::class);
    }

    public function delete(TransportSetting $setting): void
    {
        $this->_em->remove($setting);
        $this->_em->flush();
    }
}
