<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LicenseAgreementForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'license_info',
            'textarea',
            array(
                'label' => false,
                'read_only' => true,
                'data' => $options['license_info'],
                'attr' => array(
                    'rows'  => 20,
                ),
            )
        );

        $builder->add(
            'license',
            'checkbox',
            array(
                'label' => 'Accept License',
                'attr' => array(
                    'class' => 'checkbox',
                ),
                'constraints' => new NotBlank(
                    array(
                        'message' => 'You must accept the license agreement before you continue',
                    )
                ),
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'license_info',
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'license_agreement';
    }
}
