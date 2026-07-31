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

namespace SolidInvoice;

use BadMethodCallException;
use Doctrine\DBAL\Types\Type;
use Override;
use SolidInvoice\CoreBundle\Doctrine\Type\ArrayType;
use SolidInvoice\CoreBundle\Doctrine\Type\JsonArrayType;
use SolidInvoice\CoreBundle\Doctrine\Type\ObjectType;
use SolidInvoice\SaasBundle\SolidInvoiceSaasBundle;
use SolidWorx\Platform\PlatformBundle\Kernel as BaseKernel;
use SolidWorx\Platform\SaasBundle\SolidWorxPlatformSaasBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use function preg_replace;

class Kernel extends BaseKernel
{
    public function __construct(
        private readonly AppMode $mode,
        string $environment,
        bool $debug
    ) {
        parent::__construct($environment, $debug);
    }

    #[Override]
    public function boot(): void
    {
        if ($this->debug) {
            $fs = new Filesystem();
            $appModePath = $this->getCacheDir() . '/' . $this->getContainerClass() . '.app_mode';
            if (! $fs->exists($appModePath)) {
                $fs->dumpFile($appModePath, $this->mode->value);
            }

            if ($fs->readFile($appModePath) !== $this->mode->value) {
                $fs->touch($this->getConfigDir() . '/bundles.php');
                $fs->dumpFile($appModePath, $this->mode->value);
            }
        }

        parent::boot();

        if (! Type::hasType('json_array')) {
            // Only here for BC to ensure migrations work. Remove in next minor release.
            Type::addType('json_array', JsonArrayType::class);
        }

        if (! Type::hasType(ArrayType::NAME)) {
            // BC for the "array" type removed in DBAL 4 (used by historical migrations and entities).
            Type::addType(ArrayType::NAME, ArrayType::class);
        }

        if (! Type::hasType(ObjectType::NAME)) {
            // BC for the "object" type removed in DBAL 4 (used by historical migrations and Payum's Token mapping).
            Type::addType(ObjectType::NAME, ObjectType::class);
        }
    }

    #[Override]
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    #[Override]
    public function registerBundles(): iterable
    {
        yield from parent::registerBundles();

        if ($this->mode === AppMode::SAAS) {
            yield new SolidWorxPlatformSaasBundle();
            yield new SolidInvoiceSaasBundle();
        }
    }

    #[Override]
    protected function prepareContainer(ContainerBuilder $container): void
    {
        parent::prepareContainer($container);

        $container->set(AppMode::class, $this->mode);
    }

    /**
     * The default configureContainer() became private in Symfony 8 (it lives in the
     * DependencyInjection component's KernelTrait now), so the default imports are
     * replicated here instead of calling the parent method.
     */
    protected function configureContainer(ContainerConfigurator $container): void
    {
        $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());

        $container->import($configDir . '/{packages}/*.{php,yaml}');
        $container->import($configDir . '/{packages}/' . $this->environment . '/*.{php,yaml}');
        $container->import($configDir . '/{services}.php');
        $container->import($configDir . '/{services}_' . $this->environment . '.php');

        if ('staging' === $this->environment) {
            // Staging should mirror production, so load all prod config
            $container->import($configDir . '/{packages}/prod/*.{php,yaml}');
            $container->import($configDir . '/{services}_prod.php');
        }

        $bundles = $this->getBundles();

        if (($bundles['SolidWorxPlatformSaasBundle'] ?? null) instanceof SolidWorxPlatformSaasBundle) {
            $container->import($configDir . '/{packages}/saas/*.{php,yaml}');
        }
    }

    #[Override]
    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        parent::configureRoutes($routes);

        $bundles = $this->getBundles();

        if (($bundles['SolidWorxPlatformSaasBundle'] ?? null) instanceof SolidWorxPlatformSaasBundle) {
            $configDir = preg_replace('{/config$}', '/{config}', $this->getConfigDir());
            $routes->import($configDir . '/{routes}/saas/*.{php,yaml}');

        }
    }

    private function getConfigDir(): string
    {
        return $this->getProjectDir() . '/config';
    }

    #[Override]
    public function __serialize(): array
    {
        return [
            'mode' => $this->mode,
            'environment' => $this->environment,
            'debug' => $this->debug,
        ];
    }

    #[Override]
    public function __unserialize(array $data): void
    {
        $environment = $data['environment'] ?? $data["\0*\0environment"];
        $debug = $data['debug'] ?? $data["\0*\0debug"];
        $mode = $data['mode'] ?? $data["\0*\0mode"];

        if (\is_object($environment) || \is_object($debug)) {
            throw new BadMethodCallException('Cannot unserialize ' . self::class);
        }

        $this->__construct($mode, $environment, $debug);
    }
}
