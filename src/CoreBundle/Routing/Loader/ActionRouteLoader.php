<?php
/**
 * This file is part of the CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of the CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace CSBill\CoreBundle\Routing\Loader;

use Doctrine\Common\Inflector\Inflector;
use FOS\RestBundle\Routing\Loader\ClassUtils;
use Symfony\Component\Config\Exception\FileLoaderLoadException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ActionRouteLoader extends Loader
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
        $bundles = $this->kernel->getBundles();

        $collection = new RouteCollection();

        foreach ($bundles as $bundle) {
            //var_dump($bundle->getNamespace());

            try {
                $dir = $this->locator->locate(sprintf('@%s/Action/{**/*.php,*.php}', $bundle->getName()), null, false);
            } catch (FileLoaderLoadException|\InvalidArgumentException $e) {
                continue;
            }

            var_dump($bundle->getNamespace(), $dir[0]);
            var_dump(substr($dir[0], strpos($dir[0], '/Action/') + 8));
            exit;

            $actions = Finder::create()
                ->files()
                ->in($dir)
                //->depth()
                ->name("*.php")
                ->followLinks()
                ->getIterator();

            //$collection->addResource(new DirectoryResource($dir));

            $namespace = $bundle->getNamespace();
            $bundleName = Inflector::tableize(str_replace('Bundle', '', substr($namespace, strrpos($namespace, '\\') + 1)));

            /* @var SplFileInfo $action */
            foreach ($actions as $action) {
                $actionName = Inflector::tableize($action->getBasename('.php'));
                $controller = ClassUtils::findClassInFile($action->getRealPath());

                //var_dump($actionName, $controller);
                var_dump($action->getPath(), $action->getRealPath(), $action->getRelativePath(), $action->getRelativePathname(), $action->getPathInfo());
                exit;

                $route = new Route(sprintf('%s/%s', $type, $actionName));

                $route->addOptions(['expose' => true])
                    //->setMethods(['POST'])
                    ->setDefault('_controller', $controller);

                $collection->add(sprintf('%s_%s_%s', $bundleName, $type, $actionName), $route);
            }
        }

        echo 'DONE';

        exit;

        $dir = $this->locator->locate($resource);

        if (!is_dir($dir)) {
            // @TODO: Throw exception when resource is not a valid directory
            return null;
        }



        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'action' === $type;
    }
}