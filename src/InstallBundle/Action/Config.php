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

final class Config
{
    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

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
        $availableDrivers = PDO::getAvailableDrivers();

        // We can't support sqlite at the moment, since it requires a physical file
        if (in_array('sqlite', $availableDrivers, true)) {
            unset($availableDrivers[array_search('sqlite', $availableDrivers, true)]);
        }

        $drivers = array_combine(
            array_map(
                static function ($value): string {
                    return sprintf('pdo_%s', $value);
                },
                $availableDrivers
            ),
            $availableDrivers
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

            $tmpConnection = DriverManager::getConnection([
                'host' => $config['database_host'] ?? null,
                'port' => $config['database_port'] ?? null,
                'name' => $config['database_name'] ?? null,
                'user' => $config['database_user'] ?? null,
                'password' => null,
                'driver' => $config['database_driver'] ?? null,
            ]);

            $config['database_version'] = $tmpConnection->getWrappedConnection()->getServerVersion();

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
