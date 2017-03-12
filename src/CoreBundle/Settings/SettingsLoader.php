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

namespace CSBill\CoreBundle\Settings;

use CSBill\CoreBundle\ConfigWriter;
use CSBill\MoneyBundle\Form\Type\CurrencyType;
use CSBill\SettingsBundle\Entity\Setting;
use CSBill\SettingsBundle\Loader\SettingsLoaderInterface;
use Symfony\Component\Yaml\Yaml;

class SettingsLoader implements SettingsLoaderInterface
{
    /**
     * @var \AppKernel
     */
    protected $kernel;

    /**
     * @var array
     */
    protected $mailerTransports = [
        '' => 'Choose Mail Transport',
        'mail' => 'PHP Mail',
        'sendmail' => 'Sendmail',
        'smtp' => 'SMTP',
        'gmail' => 'Gmail',
    ];

    /**
     * @var ConfigWriter
     */
    private $configWriter;

    /**
     * @param \AppKernel   $kernel
     * @param ConfigWriter $configWriter
     */
    public function __construct(\AppKernel $kernel, ConfigWriter $configWriter)
    {
        $this->kernel = $kernel;
        $this->configWriter = $configWriter;
    }

    /**
     * @param array $settings
     *
     * @return array
     */
    protected function getEmailSettings(array $settings = [])
    {
        $transport = new Setting();
        $transport->setKey('transport')
            ->setValue($settings['mailer_transport'])
            ->setType('select2')
            ->setOptions($this->mailerTransports);

        $host = new Setting();
        $host->setKey('host')
            ->setValue($settings['mailer_host']);

        $port = new Setting();
        $port->setKey('port')
            ->setValue($settings['mailer_port']);

        $encryption = new Setting();
        $encryption->setKey('encryption')
            ->setValue($settings['mailer_encryption'])
            ->setType('select2')
            ->setOptions(['' => 'None', 'ssl' => 'SSL', 'tls' => 'TLS']);

        $user = new Setting();
        $user->setKey('user')
            ->setValue($settings['mailer_user']);

        $password = new Setting();
        $password->setKey('password')
            ->setValue($settings['mailer_password'])
            ->setType('password');

        return [
            'transport' => $transport,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'user' => $user,
            'password' => $password,
        ];
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        $settings = $this->getYamlParameters();

        $currency = new Setting();
        $currency->setKey('currency')
            ->setValue($settings['currency'])
            ->setType(CurrencyType::class);

        $emailSettings = $this->getEmailSettings($settings);

        return [
            'system' => [
                'general' => [
                    'currency' => $currency,
                ],
            ],
            'email' => [
                'sending_options' => $emailSettings,
            ],
        ];
    }

    /**
     * @param array $settings
     */
    public function saveSettings(array $settings = [])
    {
        $parameters = [];

        // Currency Options
        /** @var Setting $currency */
        $currency = $settings['system']['general']['currency'];

        // Email Options
        /** @var Setting $transport */
        $transport = $settings['email']['sending_options']['transport'];

        /** @var Setting $host */
        $host = $settings['email']['sending_options']['host'];

        /** @var Setting $port */
        $port = $settings['email']['sending_options']['port'];

        /** @var Setting $encryption */
        $encryption = $settings['email']['sending_options']['encryption'];

        /** @var Setting $user */
        $user = $settings['email']['sending_options']['user'];

        /** @var Setting $password */
        $password = $settings['email']['sending_options']['password'];

        if ($password->getValue() !== '') {
            $parameters['mailer_password'] = $password->getValue();
        }

        if ('gmail' === $transport->getValue()) {
            $parameters['mailer_host'] = null;
            $parameters['mailer_port'] = null;
            $parameters['mailer_encryption'] = null;
        } elseif ('sendmail' === $transport->getValue() || 'mail' === $transport->getValue()) {
            $parameters['mailer_host'] = null;
            $parameters['mailer_port'] = null;
            $parameters['mailer_encryption'] = null;
            $parameters['mailer_user'] = null;
            $parameters['mailer_password'] = null;
        } else {
            $parameters['mailer_user'] = $user->getValue();
            $parameters['mailer_encryption'] = $encryption->getValue();
            $parameters['mailer_port'] = $port->getValue();
            $parameters['mailer_host'] = $host->getValue();
            $parameters['mailer_transport'] = $transport->getValue();
        }

        $parameters['currency'] = $currency->getValue();

        $this->configWriter->dump($parameters);
    }

    /**
     * @return mixed
     */
    protected function getYamlParameters()
    {
        $configFile = $this->getParametersPath();

        $parameters = Yaml::parse(file_get_contents($configFile));

        return $parameters['parameters'];
    }

    /**
     * @return string
     */
    protected function getParametersPath()
    {
        return $this->kernel->getConfigDir().'/parameters.yml';
    }
}
