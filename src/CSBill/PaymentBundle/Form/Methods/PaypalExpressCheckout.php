<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Form\Methods;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaypalExpressCheckout extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'username',
            'text',
            array(
                'constraints' => new NotBlank()
            )
        );

        $builder->add(
            'password',
            'password'
        );

        $builder->add(
            'signature',
            'text',
            array(
                'constraints' => new NotBlank()
            )
        );

        $builder->add(
            'sandbox',
            'checkbox',
            array(
                'required' => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'paypal_express_checkout';
    }
}