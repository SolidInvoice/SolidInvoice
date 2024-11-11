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
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Throwable;
use function is_array;

class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * @param array<string, string> $settings
     *
     * @throws InvalidArgumentException|Throwable
     */
    public function save(array $settings): void
    {
        $settings = $this->flatten($settings);
        $entityManager = $this->getEntityManager();

        try {
            $entityManager->wrapInTransaction(function () use ($settings): void {
                foreach ($settings as $key => $value) {
                    $this->createQueryBuilder('s')
                        ->update()
                        ->set('s.value', ':val')
                        ->where('s.key = :key')
                        ->setParameter('key', $key)
                        ->setParameter('val', empty($value) ? null : $value)
                        ->getQuery()
                        ->execute();

                    if ('system/company/company_name' === $key) {
                        $this->getEntityManager()->getRepository(Company::class)->updateCompanyName($value);
                    }
                }
            });
        } finally {
            // Detach the entities, to not keep previous setting values
            $unitOfWork = $entityManager->getUnitOfWork();
            $entities = $unitOfWork->getIdentityMap()[Setting::class] ?? [];

            foreach ($entities as $entity) {
                $entityManager->detach($entity);
            }
        }
    }

    public function remove(string $key): void
    {
        $this->_em->remove($this->findOneBy(['key' => $key]));
        $this->_em->flush();
    }

    /**
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = [...$result, ...$this->flatten($value, $prefix . $key . '/')];
            } else {
                $result[$prefix . $key] = $value;
            }
        }

        return $result;
    }
}
