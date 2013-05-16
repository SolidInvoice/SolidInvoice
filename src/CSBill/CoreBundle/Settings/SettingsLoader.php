<?php

namespace CSBill\CoreBundle\Settings;

use CS\SettingsBundle\Loader\SettingsLoaderInterface;
use CS\SettingsBundle\Model\Setting;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Locale\Stub\StubLocale;

class SettingsLoader implements SettingsLoaderInterface
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    protected function getEmailSettings(array $settings = array())
    {
        $transportOptions = array('sendmail', 'mail', 'gmail', 'smtp');

        $transport = new Setting();
        $transport->setKey('transport')
            ->setValue($settings['mailer_transport'])
            ->setType('choice')
            ->setOptions(array_combine($transportOptions, $transportOptions));

        $host = new Setting();
        $host->setKey('host')
            ->setValue($settings['mailer_host'])
            ->setDescription('Only necessary if using smtp or gmail');

        $user = new Setting();
        $user->setKey('user')
            ->setValue($settings['mailer_user'])
            ->setDescription('Only necessary if using smtp or gmail');

        $password = new Setting();
        $password->setKey('password')
                ->setValue($settings['mailer_password'])
                ->setType('password')
                ->setDescription('Only necessary if using smtp or gmail');

        return array(
            'transport' => $transport,
            'host' => $host,
            'user' => $user,
            'password' => $password,
        );
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
                ->setType('chosen')
                ->setOptions(StubLocale::getDisplayCurrencies('en'));

        $emailSettings = $this->getEmailSettings($settings);

        return array(
                'system' => array(
                    'general' => array(
                        'currency' => $currency
                    )
                ),
                'email' => array(
                    'sending_options' => $emailSettings
                )
        );
    }

    /**
     * @param array $settings
     */
    public function saveSettings(array $settings = array())
    {
        $defaults = $this->getYamlParameters();

        // Currency Options
        $currency = $settings['system']['general']['currency'];
        $defaults['currency'] = $currency->getValue();

        // Email Options
        $transport = $settings['email']['sending_options']['transport'];
        $defaults['mailer_transport'] = $transport->getValue();

        $host = $settings['email']['sending_options']['host'];
        $defaults['mailer_host'] = $host->getValue();

        $user = $settings['email']['sending_options']['user'];
        $defaults['mailer_user'] = $user->getValue();

        $password = $settings['email']['sending_options']['password'];
        $defaults['mailer_password'] = $password->getValue();

        $this->dumpParameters($defaults);
    }

    /**
     * @return mixed
     */
    protected function getYamlParameters()
    {
        $configFile = $this->getParametersPath();

        $parameters = Yaml::Parse(file_get_contents($configFile));

        return $parameters['parameters'];
    }

    /**
     * @param  array $parameters
     * @return int
     */
    protected function dumpParameters(array $parameters = array())
    {
        $configFile = $this->getParametersPath();

        $parameters = Yaml::dump(array('parameters' => $parameters));

        return file_put_contents($configFile, $parameters, LOCK_EX);
    }

    /**
     * @return string
     */
    protected function getParametersPath()
    {
        return $this->kernel->getRootDir().'/config/parameters.yml';
    }
}
