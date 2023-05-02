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

use Doctrine\DBAL\Exception;
use Money\Currency;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use Throwable;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\SystemConfigTest
 */
class SystemConfig
{
    final public const CURRENCY_CONFIG_PATH = 'system/company/currency';

    /**
     * @var array<string, string>
     */
    private static array $settings = [];

    public function __construct(private readonly ?string $installed, private readonly SettingsRepository $repository)
    {
    }

    public function get(string $key): ?string
    {
        if (null === $this->installed) {
            return null;
        }

        $setting = $this->repository->findOneBy(['key' => $key]);

        if (null === $setting) {
            return null;
        }

        return $setting->getValue();
    }

    /**
     * @throws Throwable
     */
    public function set(string $path, mixed $value): void
    {
        $this->repository->save([$path => $value]);
        self::$settings = [];
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        $this->load();

        return self::$settings;
    }

    private function load(): void
    {
        if ([] === self::$settings) {
            try {
                $settings = $this->repository
                    ->createQueryBuilder('c')
                    ->select('c.key', 'c.value')
                    ->orderBy('c.key')
                    ->getQuery()
                    ->getArrayResult();
            } catch (Exception) {
                return;
            }

            self::$settings = array_combine(array_column($settings, 'key'), array_column($settings, 'value'));
        }
    }

    public function remove(string $key): void
    {
        $this->repository->remove($key);
        self::$settings = [];
    }

    public function getCurrency(): ?Currency
    {
        $currency = $this->get(self::CURRENCY_CONFIG_PATH);

        if (null === $currency) {
            return null;
        }

        return new Currency($this->get(self::CURRENCY_CONFIG_PATH));
    }
}
