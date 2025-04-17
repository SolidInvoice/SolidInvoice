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

use Doctrine\DBAL\Types\Type;
use SolidInvoice\CoreBundle\Doctrine\Type\JsonArrayType;
use SolidWorx\FormHandler\FormHandler;
use SolidWorx\FormHandler\FormHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        configureContainer as private configureContainerTrait;
    }

    public function boot(): void
    {
        parent::boot();

        if (! Type::hasType('json_array')) {
            // Only here for BC to ensure migrations work. Remove in next minor release.
            Type::addType('json_array', JsonArrayType::class);
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->configureContainerTrait($container, $loader, $builder);
        $builder->registerForAutoconfiguration(FormHandlerInterface::class)
            ->addTag('form.handler');

        $builder->setAlias(FormHandler::class, 'solidworx.form_handler');
    }
}
