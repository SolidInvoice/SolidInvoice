<?php

namespace CSBill\CoreBundle\Tests;

require_once (__DIR__ . '/../../../../app/AppKernel.php');

abstract class KernelAwareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \AppKernel
     */
    protected $kernel;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @return null
     */
    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    /**
     * @return null
     */
    public function tearDown()
    {
        $this->kernel->shutdown();

        parent::tearDown();
    }
}