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

namespace SolidInvoice\CoreBundle\Routing\Loader;

use Doctrine\Inflector\InflectorFactory;
use SolidInvoice\CoreBundle\Util\ClassUtil;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

abstract class AbstractDirectoryLoader extends Loader
{
    /**
     * @var FileLocatorInterface
     */
    private $locator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(FileLocatorInterface $locator, KernelInterface $kernel)
    {
        $this->locator = $locator;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): ?RouteCollection
    {
        $dir = $this->locator->locate($resource);

        if (!is_dir($dir)) {
            // @TODO: Throw exception when resource is not a valid directory
            return null;
        }

        $actions = Finder::create()
            ->files()
            ->in($dir)
            ->depth(0)
            ->name('*.php')
            ->followLinks()
            ->getIterator();

        $collection = new RouteCollection();
        $collection->addResource(new DirectoryResource($dir));

        $inflector = InflectorFactory::create()->build();

        /* @var Bundle $bundle */
        $bundle = $this->kernel->getBundle(substr($resource, 1, strpos($resource, '/') - 1));
        $namespace = $bundle->getNamespace();
        $bundleName = $inflector->tableize(str_replace('Bundle', '', substr($namespace, strrpos($namespace, '\\') + 1)));

        /* @var SplFileInfo $action */
        foreach ($actions as $action) {
            $actionName = $inflector->tableize($action->getBasename('.php'));
            $controller = ClassUtil::findClassInFile($action->getRealPath());

            if (null === $controller) {
                continue;
            }

            $route = new Route(sprintf('%s/%s', $type, $actionName));

            $route->addOptions(['expose' => true])
                ->setMethods(['POST'])
                ->setDefault('_controller', $controller);

            $collection->add(sprintf('%s_%s_%s', $bundleName, $type, $actionName), $route);
        }

        return $collection;
    }
}
