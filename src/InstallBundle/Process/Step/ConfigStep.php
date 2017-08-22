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
use Symfony\Component\Form\Form;

class ConfigStep extends AbstractControllerStep
{
    /**
     * Array of currently implemented database drivers.
     *
     * @var array
     */
    protected $implementedDrivers = [
        'mysql',
    ];

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
        return $this->render('SolidInvoiceInstallBundle:Flow:config.html.twig', ['form' => $this->getForm()->createView()]);
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    private function getForm(): Form
    {
        $availableDrivers = array_intersect($this->implementedDrivers, \PDO::getAvailableDrivers());
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
                'host' => $config['database_host'],
                'port' => $config['database_port'],
                'name' => $config['database_name'],
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
                $key = sprintf('database_%s', $key);
                $config[$key] = $param;
            }

            // sets the database details
            foreach ($data['email_settings'] as $key => $param) {
                $key = sprintf('mailer_%s', $key);
                $config[$key] = $param;
            }

            $this->get('solidinvoice.core.config_writer')->dump($config);

            return $this->complete();
        }

        return $this->render('SolidInvoiceInstallBundle:Flow:config.html.twig', ['form' => $form->createView()]);
    }
}
