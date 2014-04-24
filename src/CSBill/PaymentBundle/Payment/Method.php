<?php

namespace CSBill\PaymentBundle\Payment;

use Symfony\Component\Form\FormBuilderInterface;

class Method
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $context;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @param string $name
     * @param string $context
     * @param array  $settings
     */
    public function __construct($name, $context, array $settings = null)
    {
        $this->name = $name;
        $this->context = $context;
        $this->settings = $settings;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param array $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function buildSettingsForm(FormBuilderInterface $builder)
    {
        if (!empty($this->settings)) {
            foreach ($this->settings as $setting) {
                $builder->add($setting['name'], $setting['type']);
            }
        }
    }
}
