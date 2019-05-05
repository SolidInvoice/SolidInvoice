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

namespace SolidInvoice\InstallBundle\Process\Step;

use SolidInvoice\InstallBundle\Form\Step\ConfigStepForm;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\AbstractControllerStep;
use Symfony\Component\Form\FormInterface;

class ConfigStep extends AbstractControllerStep
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
     * {@inheritdoc}
     */
    public function displayAction(ProcessContextInterface $context)
    {
        return $this->render('@SolidInvoiceInstall/Flow/config.html.twig', ['form' => $this->getForm()->createView()]);
    }

    private function getForm(): FormInterface
    {
        $availableDrivers = \PDO::getAvailableDrivers();
        $drivers = array_combine(
            array_map(
                function ($value): string {
                    return sprintf('pdo_%s', $value);
                },
                $availableDrivers
            ),
            $availableDrivers
        );

        $config = $this->get('solidinvoice.core.config_writer')->getConfigValues();

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
            'action' => $this->generateUrl(
                'sylius_flow_forward',
                [
                    'scenarioAlias' => 'install',
                    'stepName' => 'config',
                ]
            ),
        ];

        return $this->createForm(ConfigStepForm::class, $data, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function forwardAction(ProcessContextInterface $context)
    {
        $request = $context->getRequest();
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

            $this->get('solidinvoice.core.config_writer')->dump($config);

            return $this->complete();
        }

        return $this->render('@SolidInvoiceInstall/Flow/config.html.twig', ['form' => $form->createView()]);
    }
}
