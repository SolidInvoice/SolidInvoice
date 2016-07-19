<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Form\Type;

use CSBill\SettingsBundle\Model\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class Settings.
 */
class Settings extends AbstractType
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($this->settings as $key => $setting) {
            if (is_array($setting)) {
                $builder->add($key, new self($setting));
            } else {
                /* @var \CSBill\SettingsBundle\Model\Setting $setting */
                $options = [
                    'help' => $setting->getDescription(),
                    'required' => false,
                ];

                $type = $this->getFieldType($setting, $options);

                $builder->add($setting->getKey(), $type, $options);
            }
        }
    }

    /**
     * @param Setting $setting
     * @param array   $options
     *
     * @return string
     */
    protected function getFieldType(Setting $setting, array &$options = [])
    {
        $type = $setting->getType();

        if ('radio' === $type) {
            $type = ChoiceType::class;
            $options['expanded'] = true;
            $options['multiple'] = false;
        }

        if (in_array($type, ['choice', 'select2'])) {
            $settingOptions = $setting->getOptions();

            $options['choices'] = $settingOptions;
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'settings';
    }
}
