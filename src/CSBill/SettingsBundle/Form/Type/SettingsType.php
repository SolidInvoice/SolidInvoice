<?php

/*
 * This file is part of the CSBillSettingsBundle package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\SettingsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use CSBill\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class SettingsType
 * @package CSBill\SettingsBundle\Form\Type
 */
class SettingsType extends AbstractType
{
    /**
     * @var SettingsManager
     */
    protected $manager;

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.AbstractType::buildForm()
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->manager = $options['manager'];

        $settings = $this->manager->getSettings();

        foreach ($settings as $section => $setting) {
            $builder->add($section, new Settings($this->manager->get($section)));
        }
    }

    /**
     * (non-PHPdoc)
     * @see Symfony\Component\Form.FormTypeInterface::getName()
     */
    public function getName()
    {
        return 'settings';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('manager'));
    }
}
