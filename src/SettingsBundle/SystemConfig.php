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

namespace SolidInvoice\SettingsBundle;

use SolidInvoice\SettingsBundle\Repository\SettingsRepository;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\SystemConfigTest
 */
class SystemConfig
{
    /**
     * @var SettingsRepository
     */
    private $repository;

    private static $settings;

    public function __construct(SettingsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function get(string $key)
    {
        $this->load();

        if (array_key_exists($key, self::$settings)) {
            return self::$settings[$key];
        }

        return null;
    }

    public function set(string $path, $value): void
    {
        $this->repository->save([$path => $value]);
        self::$settings = null;
    }

    public function getAll()
    {
        $this->load();

        return self::$settings;
    }

    private function load(): void
    {
        if (! self::$settings) {
            $settings = $this->repository
                ->createQueryBuilder('c')
                ->select('c.key', 'c.value')
                ->orderBy('c.key')
                ->getQuery()
                ->getArrayResult();

            self::$settings = array_combine(array_column($settings, 'key'), array_column($settings, 'value'));
        }
    }

    public function remove(string $key): void
    {
        $this->repository->remove($key);
        self::$settings = null;
    }
}
