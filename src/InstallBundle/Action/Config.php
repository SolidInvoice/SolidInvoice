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
use PDO;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Templating\Template;
use SolidInvoice\InstallBundle\Form\Step\ConfigStepForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use function array_intersect;
use function array_map;
use function assert;
use function Symfony\Component\String\u;

final class Config
{
    private ConfigWriter $configWriter;

    private RouterInterface $router;

    private FormFactoryInterface $formFactory;

    public function __construct(ConfigWriter $configWriter, RouterInterface $router, FormFactoryInterface $formFactory)
    {
        $this->configWriter = $configWriter;
        $this->router = $router;
        $this->formFactory = $formFactory;
    }

    public function __invoke(Request $request)
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            return $this->handleForm($request);
        }

        return $this->render();
    }

    private function getForm(): FormInterface
    {
        $availablePdoDrivers = array_intersect(
            array_map(static fn (string $driver) => "pdo_$driver", PDO::getAvailableDrivers()),
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

        $config = $this->configWriter->getConfigValues();

        $data = [
            'database_config' => [
                'host' => $config['database_host'] ?? null,
                'port' => $config['database_port'] ?? null,
                'name' => $config['database_name'] ?? null,
                'user' => $config['database_user'] ?? null,
                'password' => null,
                'driver' => $config['database_driver'] ?? null,
            ],
        ];

        return $this->formFactory->create(ConfigStepForm::class, $data, ['drivers' => $drivers]);
    }

    public function handleForm(Request $request)
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

            $nativeConnection = DriverManager::getConnection([
                'host' => $config['database_host'] ?? null,
                'port' => $config['database_port'] ?? null,
                'name' => $config['database_name'] ?? null,
                'user' => $config['database_user'] ?? null,
                'password' => $config['database_password'] ?? null,
                'driver' => $config['database_driver'] ?? null,
            ])->getNativeConnection();

            assert($nativeConnection instanceof PDO);

            $config['database_version'] = $nativeConnection->getAttribute(PDO::ATTR_SERVER_VERSION);

            $this->configWriter->dump($config);

            return new RedirectResponse($this->router->generate('_install_install'));
        }

        return $this->render($form);
    }

    private function render(?FormInterface $form = null): Template
    {
        return new Template('@SolidInvoiceInstall/config.html.twig', ['form' => ($form ?: $this->getForm())->createView()]);
    }
}
