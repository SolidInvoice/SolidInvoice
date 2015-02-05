<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Process\Step;

use Doctrine\DBAL\DriverManager;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ControllerStep;
use Symfony\Component\HttpFoundation\JsonResponse;

class InstallStep extends ControllerStep
{
    /**
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        $request = $this->getRequest();

        if ($request->query->has('action')) {
            $result = array();

            switch ($request->query->get('action')) {
                case 'createdb' :
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
                case 'migrations' :
                    $migration = $this->get('csbill.installer.database.migration');

                    try {
                        $migration->migrate();

                        $result['success'] = true;
                    } catch (\Exception $e) {
                        $result['success'] = false;
                        $result['message'] = $e->getMessage();
                    }

                    break;
                case 'fixtures' :
                    $fixtureLoader = $this->get('csbill.installer.database.fixtures');

                    try {
                        $fixtureLoader->execute();
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
