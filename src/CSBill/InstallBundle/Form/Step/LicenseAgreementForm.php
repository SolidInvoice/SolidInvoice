<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
                    'rows'  => 20
                )
            )
        );

        $builder->add(
            'license',
            'checkbox',
            array(
                'label' => 'Accept License',
                'attr' => array(
                    'class' => 'checkbox'
                ),
                'constraints' => new NotBlank(array(
                        'message' => 'You must accept the license agreement before you continue'
                    ))
            )
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array(
            'license_info'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'license_agreement';
    }
}
