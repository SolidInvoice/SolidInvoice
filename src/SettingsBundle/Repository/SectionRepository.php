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

/**
 * Class SectionRepository.
 */
class SectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Returns an array of all the top-level sections.
     *
     * @param string $cacheKey
     */
    public function getTopLevelSections(bool $cache = false, $cacheKey = 'solidinvoice_settings_top_section_sections', int $lifetime = 604800): array
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
