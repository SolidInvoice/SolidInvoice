<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Process\Step;

use Doctrine\DBAL\DriverManager;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\AbstractControllerStep;
use Symfony\Component\HttpFoundation\JsonResponse;

class InstallStep extends AbstractControllerStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $request = $context->getRequest();

        if ($request->query->has('action')) {
            $result = [];

            switch ($request->query->get('action')) {
                case 'createdb':
                    $connection = $this->get('doctrine')->getConnection();
                    $params = $connection->getParams();
                    $dbName = $params['dbname'];

                    unset($params['dbname']);

                    $tmpConnection = DriverManager::getConnection($params);

                    try {
                        $tmpConnection->getSchemaManager()->createDatabase($dbName);
                        $result['success'] = true;
                    } catch (\Exception $e) {
                        if (false !== strpos($e->getMessage(), 'database exists')) {
                            $result['success'] = true;
                        } else {
                            $result['success'] = false;
                            $result['message'] = $e->getMessage();
                        }
                    }

                    break;
                case 'migrations':
                    $migration = $this->get('csbill.installer.database.migration');

                    try {
                        $migration->migrate();

                        $result['success'] = true;
                    } catch (\Exception $e) {
                        $result['success'] = false;
                        $result['message'] = $e->getMessage();
                    }

                    break;
            }

            return new JsonResponse($result);
        }

        return $this->render('CSBillInstallBundle:Flow:install.html.twig');
    }
}
