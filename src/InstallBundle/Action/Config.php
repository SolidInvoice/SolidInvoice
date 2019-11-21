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

namespace SolidInvoice\InstallBundle\Action;

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
     * @var array
     */
    protected $mailerTransports = [
        'sendmail' => 'Sendmail',
        'smtp' => 'SMTP',
        'gmail' => 'Gmail',
    ];

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
        $availableDrivers = \PDO::getAvailableDrivers();

        // We can't support sqlite at the moment, since it requires a physical file
        if (in_array('sqlite', $availableDrivers, true)) {
            unset($availableDrivers[array_search('sqlite', $availableDrivers)]);
        }

        $drivers = array_combine(
            array_map(
                function ($value): string {
                    return sprintf('pdo_%s', $value);
                },
                $availableDrivers
            ),
            $availableDrivers
        );

        $config = $this->configWriter->getConfigValues();

        $data = [
            'database_config' => [
                'host' => $_ENV['database_host'] ?? $config['env(database_host)'],
                'port' => $_ENV['database_port'] ?? $config['env(database_port)'],
                'name' => $_ENV['database_name'] ?? $config['env(database_name)'],
                'user' => $_ENV['database_user'] ?? $config['env(database_user)'],
                'password' => $_ENV['database_password'] ?? $config['env(database_password)'],
                'driver' => $_ENV['database_driver'] ?? null,
            ],
        ];

        $options = [
            'drivers' => $drivers,
            'mailer_transports' => $this->mailerTransports,
        ];

        return $this->formFactory->create(ConfigStepForm::class, $data, $options);
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
                $key = sprintf('env(database_%s)', $key);
                $config[$key] = $param;
            }

            // sets the database details
            foreach ($data['email_settings'] as $key => $param) {
                $key = sprintf('env(mailer_%s)', $key);
                $config[$key] = $param;
            }

            $this->configWriter->dump($config);

            return new RedirectResponse($this->router->generate('_install_install'));
        }

        return $this->render($form);
    }

    protected function render(?FormInterface $form = null): Template
    {
        return new Template('@SolidInvoiceInstall/config.html.twig', ['form' => ($form ?: $this->getForm())->createView()]);
    }
}
