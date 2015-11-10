<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CSBillPaymentExtension extends Extension
{
    const NS = 'CSBill\PaymentBundle\Form\Methods';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('payment_api.yml');

        $files = Finder::create()
            ->in(__DIR__.'/../Form/Methods')
            ->files()
            ->ignoreDotFiles(true)
            ->getIterator();

        /** @var \SplFileInfo $file */
        foreach ($files as $key => $file) {
            $class = $file->getBasename('.'.$file->getExtension());

            if (class_exists($class = self::NS.'\\'.$class)) {
                $this->addPaymentForm($container, $class);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $class
     *
     * @return Definition
     */
    private function addPaymentForm(ContainerBuilder $container, $class)
    {
        /** @var FormInterface $instance */
        $instance = new $class;
        $name = $instance->getName();

        $definition = new Definition($class);
        $definition->addTag('form.type', ['alias' => $name]);

        $container->setDefinition(sprintf('csbill_payment.method.%s', $name), $definition);
    }
}
