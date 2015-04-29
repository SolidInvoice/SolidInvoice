<?php
/**
 * This file is part of the CSBill project.
 * 
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
 */

namespace CSBill\CoreBundle\Behat;

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use CSBill\CoreBundle\Kernel\ContainerClassKernelInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class DefaultContext extends RawMinkContext implements Context, KernelAwareContext
{
    /**
     * @var ContainerClassKernelInterface
     */
    protected $kernel;

    /**
     * Faker.
     *
     * @var Generator
     */
    protected $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    /**
     * Sets Kernel instance.
     *
     * @param KernelInterface $kernel
     */
    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }
}