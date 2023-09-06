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
use RuntimeException;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;
use function var_dump;

/**
 * @see \SolidInvoice\SettingsBundle\Tests\SystemConfigTest
 */
class SystemConfig
{
    public const CURRENCY_CONFIG_PATH = 'system/company/currency';

    private SettingsRepository $repository;

    /**
     * @var array<string, string>
     */
    private static array $settings = [];

    private ?string $installed;

    public function __construct(?string $installed, SettingsRepository $repository)
    {
        $this->repository = $repository;
        $this->installed = $installed;
    }

    public function get(string $key): ?string
    {
        $cloner = new VarCloner();
        $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
        $dumper = new CliDumper();

        VarDumper::setHandler(function ($var) use ($cloner, $dumper) {
            var_dump($dumper->dump($cloner->cloneVar($var), true));
        });

        VarDumper::dump($this->installed);

        if (null === $this->installed) {
            return null;
        }

        VarDumper::dump($key);

        $setting = $this->repository->findOneBy(['key' => $key]);

        VarDumper::dump($setting);
        VarDumper::dump($this->repository->findAll());

        if (null === $setting) {
            return null;
        }

        return $setting->getValue();
    }

    /**
     * @param mixed $value
     * @throws Throwable
     */
    public function set(string $path, $value): void
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
            } catch (Exception $e) {
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

    public function getCurrency(): Currency
    {


        $currency = $this->get(self::CURRENCY_CONFIG_PATH);

        if (null === $currency) {
            throw new RuntimeException('No currency set');
        }

        return new Currency($currency);
    }
}
