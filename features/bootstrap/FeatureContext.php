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

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Doctrine\Common\Persistence\ManagerRegistry;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Knp\FriendlyContexts\Context\EntityContext;

class FeatureContext implements Context
{
    private const FILTER = 'softdeleteable';

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var EntityContext
     */
    private $entityContext;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @BeforeScenario
     */
    public function migrateDatabase(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();
        $this->entityContext = $environment->getContext(EntityContext::class);
    }

    /**
     * @Given /^[T|t]here are no (.+)$/
     */
    public function cleanTable($entity)
    {
        $method = new ReflectionMethod($this->entityContext, 'resolveEntity');
        $method->setAccessible(true);

        $entityClass = $method->invoke($this->entityContext, $entity)->getName();
        $objectManager = $this->doctrine->getManager();
        $repository = $objectManager->getRepository($entityClass);
        $filters = $objectManager->getFilters();
        $originalEventListeners = [];
        $meta = $objectManager->getClassMetadata($entityClass);

        if ($filters->has(self::FILTER)) {
            $filters->disable(self::FILTER);
        }

        foreach ($objectManager->getEventManager()->getListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof SoftDeleteableListener) {

                    $originalEventListeners[$eventName] = $listener;
                    $objectManager->getEventManager()->removeEventListener($eventName, $listener);

                    continue 2;
                }
            }
        }

        foreach ($repository->findAll() as $item) {
            $objectManager->remove($item);
        }

        $objectManager->flush();

        $resetAutoIncrement = function ($entityClass) use ($objectManager) {
            $tableName = $objectManager->getClassMetadata($entityClass)->getTableName();
            $connection = $objectManager->getConnection();
            $connection->exec("ALTER TABLE {$tableName} AUTO_INCREMENT = 1;");
        };

        $resetAutoIncrement($entityClass);

        foreach ($meta->getAssociationNames() as $associationName) {
            $resetAutoIncrement($meta->getAssociationTargetClass($associationName));
        }

        $objectManager->flush();

        foreach ($originalEventListeners as $eventName => $listener) {
            $objectManager->getEventManager()->addEventListener($eventName, $listener);
        }

        if ($filters->has(self::FILTER)) {
            $filters->enable(self::FILTER);
        }
    }
}