<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Test\Traits;

use CSBill\ClientBundle\Listener\ClientListener;
use CSBill\NotificationBundle\Notification\NotificationManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Timestampable\TimestampableListener;
use Mockery as M;
use Ramsey\Uuid\Doctrine\UuidType;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bridge\Doctrine\Test\DoctrineTestHelper;
use Symfony\Component\DependencyInjection\Container;

/**
 * @codeCoverageIgnore
 */
trait DoctrineTestTrait
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @before
     */
    protected function setupDoctrine()
    {
        $config = DoctrineTestHelper::createTestConfiguration();

        $payum = new SimplifiedXmlDriver([(getcwd().'/vendor/payum/core/Payum/Core/Bridge/Doctrine/Resources/mapping') => 'Payum\\Core\\Model']);
        $payum->setGlobalBasename('mapping');

        $c = new AnnotationDriver(
            new AnnotationReader(),
            [
                getcwd().'/vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity',
                getcwd().'/vendor/gedmo/doctrine-extensions/lib/Gedmo/Translator/Entity',
                getcwd().'/vendor/gedmo/doctrine-extensions/lib/Gedmo/Loggable/Entity',
                getcwd().'/vendor/gedmo/doctrine-extensions/lib/Gedmo/Tree/Entity',
                getcwd().'/src/ClientBundle/Entity',
                getcwd().'/src/CoreBundle/Entity',
                getcwd().'/src/InvoiceBundle/Entity',
                getcwd().'/src/MoneyBundle/Entity',
                getcwd().'/src/NotificationBundle/Entity',
                getcwd().'/src/PaymentBundle/Entity',
                getcwd().'/src/QuoteBundle/Entity',
                getcwd().'/src/SettingsBundle/Entity',
                getcwd().'/src/TaxBundle/Entity',
                getcwd().'/src/UserBundle/Entity',
            ]
        );

        $driver = new MappingDriverChain();
        $driver->addDriver($payum, 'Payum\\Core\\Model');
        $driver->addDriver($c, 'Gedmo\\Translatable\\Entity');
        $driver->addDriver($c, 'Gedmo\\Translator\\Entity');
        $driver->addDriver($c, 'Gedmo\\Loggable\\Entity');
        $driver->addDriver($c, 'Gedmo\\Tree\\Entity');
        $driver->addDriver($c, 'CSBill\\ClientBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\CoreBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\InvoiceBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\MoneyBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\NotificationBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\PaymentBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\QuoteBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\SettingsBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\TaxBundle\\Entity');
        $driver->addDriver($c, 'CSBill\\UserBundle\\Entity');
        $config->setMetadataDriverImpl($driver);

        if (method_exists($this, 'getEntityNamespaces')) {
            $config->setEntityNamespaces($this->getEntityNamespaces());
        }

        $this->em = DoctrineTestHelper::createTestEntityManager($config);
        $this->em->getConnection()->getEventManager()->addEventSubscriber(new TimestampableListener());

        $clientListener = new ClientListener();

        $container = new Container();
        $mock = M::mock(NotificationManager::class);
        $mock->shouldReceive('sendNotification')
            ->zeroOrMoreTimes();
        $container->set('notification.manager', $mock);
        $clientListener->setContainer($container);

        $this->em->getConnection()->getEventManager()->addEventListener(Events::prePersist, $clientListener);
        $this->em->getConnection()->getEventManager()->addEventListener(Events::postPersist, $clientListener);
        $this->em->getConnection()->getEventManager()->addEventListener(Events::postUpdate, $clientListener);

        if (!DoctrineType::hasType('uuid')) {
            DoctrineType::addType('uuid', UuidType::class);
        }

        $this->createRegistryMock('default', $this->em);
        $this->createSchema($this->em);
    }

    protected function createRegistryMock($name, $em)
    {
        $this->registry = M::mock(ManagerRegistry::class);
        $this->registry->shouldReceive('getManager')
            ->zeroOrMoreTimes()
            ->with()
            ->andReturn($em);

        $this->registry->shouldReceive('getManager')
            ->zeroOrMoreTimes()
            ->with($name)
            ->andReturn($em);

        $this->registry->shouldReceive('getManagers')
            ->with()
            ->andReturn(['default' => $em]);

        $this->registry->shouldReceive('getManagerForClass')
            ->andReturn($em);

        return $this->registry;
    }

    protected function createSchema(EntityManager $em)
    {
        $schemaTool = new SchemaTool($em);
        $classes = [];

        if (method_exists($this, 'getEntities')) {
            foreach ($this->getEntities() as $entityClass) {
                $classes[] = $em->getClassMetadata($entityClass);
            }
        }

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }
}
