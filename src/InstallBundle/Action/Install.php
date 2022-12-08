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

namespace SolidInvoice\InstallBundle\Action;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InstallBundle\Installer\Database\Migration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class Install
{
    public function __invoke(Request $request, ManagerRegistry $doctrine, Migration $migration)
    {
        if ($request->request->has('action')) {
            $result = [];

            switch ($request->request->get('action')) {
                case 'createdb':
                    $connection = $doctrine->getConnection();
                    $params = $connection->getParams();
                    $dbName = $params['dbname'];

                    unset($params['dbname']);

                    try {
                        $tmpConnection = DriverManager::getConnection($params);
                        $tmpConnection->createSchemaManager()->createDatabase($dbName);
                        $result['success'] = true;
                    } catch (Exception | DBALException $e) {
                        if (false !== strpos($e->getMessage(), 'database exists')) {
                            $result['success'] = true;
                        } else {
                            $result['success'] = false;
                            $result['message'] = $e->getMessage();
                        }
                    }

                    break;
                case 'migrations':
                    try {
                        $migration->migrate();

                        $result['success'] = true;
                    } catch (MigrationException $e) {
                        $result['success'] = false;
                        $result['message'] = $e->getMessage();
                    }

                    break;
            }

            return new JsonResponse($result);
        }

        return new Template('@SolidInvoiceInstall/install.html.twig');
    }
}
