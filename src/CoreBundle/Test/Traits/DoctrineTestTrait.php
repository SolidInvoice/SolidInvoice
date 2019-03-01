<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Test\Traits;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Gedmo\Timestampable\TimestampableListener;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Doctrine\UuidType;
use SolidInvoice\ClientBundle\Listener\ClientListener;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
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
        $config = self::createTestConfiguration();

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
        $driver->addDriver($c, 'SolidInvoice\\ClientBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\CoreBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\InvoiceBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\MoneyBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\PaymentBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\QuoteBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\SettingsBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\TaxBundle\\Entity');
        $driver->addDriver($c, 'SolidInvoice\\UserBundle\\Entity');
        $config->setMetadataDriverImpl($driver);

        $this->em = self::createTestEntityManager($config);
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
        $this->createSchema($config);
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

    protected function createSchema(Configuration $config)
    {
        if (file_exists(sys_get_temp_dir().'/solidinvoice_tmp.db')) {
            copy(sys_get_temp_dir().'/solidinvoice_tmp.db', sys_get_temp_dir().'/solidinvoice.db');

            return;
        }

        $em = self::createTestEntityManager($config, sys_get_temp_dir().'/solidinvoice_tmp.db');
        $schemaTool = new SchemaTool($em);

        $classes = $em->getMetadataFactory()->getAllMetadata();

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);

        copy(sys_get_temp_dir().'/solidinvoice_tmp.db', sys_get_temp_dir().'/solidinvoice.db');
    }

    private static function createTestEntityManager(Configuration $config = null, string $dbPath = null): EntityManager
    {
        if (!\extension_loaded('pdo_sqlite')) {
            TestCase::markTestSkipped('Extension pdo_sqlite is required.');
        }

        if (null === $config) {
            $config = self::createTestConfiguration();
        }

        $params = [
            'driver' => 'pdo_sqlite',
            'path' => $dbPath ?? sys_get_temp_dir().'/solidinvoice.db',
        ];

        return EntityManager::create($params, $config);
    }

    private static function createTestConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setEntityNamespaces(['SymfonyTestsDoctrine' => 'Symfony\Bridge\Doctrine\Tests\Fixtures']);
        $config->setAutoGenerateProxyClasses(true);
        $config->setProxyDir(\sys_get_temp_dir());
        $config->setProxyNamespace('SymfonyTests\Doctrine');
        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));
        $config->setQueryCacheImpl(new ArrayCache());
        $config->setMetadataCacheImpl(new ArrayCache());

        return $config;
    }
}
