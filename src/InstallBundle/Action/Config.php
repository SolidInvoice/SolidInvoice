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
use Doctrine\DBAL\Exception;
use PDO;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\InstallBundle\Form\Step\ConfigStepForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function array_intersect;
use function array_map;
use function assert;
use function Symfony\Component\String\u;

final class Config extends AbstractController
{
    public function __construct(
        private readonly ConfigWriter $configWriter,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handleForm($request);
        }

        return $this->render('@SolidInvoiceInstall/config.html.twig', ['form' => $this->getForm()]);
    }

    private function getForm(): FormInterface
    {
        $availablePdoDrivers = array_intersect(
            array_map(static fn (string $driver) => "pdo_{$driver}", PDO::getAvailableDrivers()),
            DriverManager::getAvailableDrivers()
        );

        // We can't support sqlite at the moment, since it requires a physical file
        if (in_array('pdo_sqlite', $availablePdoDrivers, true)) {
            unset($availablePdoDrivers[array_search('pdo_sqlite', $availablePdoDrivers, true)]);
        }

        $drivers = array_combine(
            $availablePdoDrivers,
            array_map(static fn (string $driver) => u($driver)->replace('pdo_', '')->title()->toString(), $availablePdoDrivers)
        );

        return $this->createForm(ConfigStepForm::class, null, ['drivers' => $drivers]);
    }

    private function handleForm(Request $request): Response
    {
        $form = $this->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $config = [];

            // sets the database details
            foreach ($data['database_config'] as $key => $param) {
                $key = sprintf('database_%s', $key);
                $config[$key] = $param;
            }

            try {
                $nativeConnection = DriverManager::getConnection([
                    'host' => $config['database_host'] ?? null,
                    'port' => $config['database_port'] ?? null,
                    'name' => $config['database_name'] ?? null,
                    'user' => $config['database_user'] ?? null,
                    'password' => $config['database_password'] ?? null,
                    'driver' => $config['database_driver'] ?? null,
                ])->getNativeConnection();
            } catch (Exception $e) {
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('_install_config');
            }

            assert($nativeConnection instanceof PDO);

            $config['database_version'] = $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);

            $this->configWriter->save($config);

            return $this->redirectToRoute('_install_install');
        }

        return $this->render('@SolidInvoiceInstall/config.html.twig', ['form' => $form]);
    }
}
