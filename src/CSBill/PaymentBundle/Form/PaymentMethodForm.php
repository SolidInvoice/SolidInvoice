<?php

namespace CSBill\PaymentBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PaymentMethodForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \CSBill\PaymentBundle\Manager\PaymentMethodManager $manager */
        $manager = $options['manager'];
        $types   = array_keys($manager->getPaymentMethods());

        $options = array_combine($types, $types);

        array_walk(
            $options,
            function (&$value) {
                $value = ucwords(str_replace(array('_', '-'), ' ', strtolower($value)));

                return $value;
            }
        );

        $builder->add('name');
        $builder->add('defaultStatus');
        $builder->add('public');

        $builder->add(
            'payment_method',
            'choice',
            array(
                'choices'     => $options,
                'empty_value' => 'Please Choose',
                'attr'        => array(
                    'class' => 'chosen'
                ),
            )
        );

        $formModifier = function (FormInterface $form, $method) use ($manager) {
            $settings = (null !== $method) ? $manager->getPaymentMethod($method)->getSettings() : array();

            $form->add('settings', new PaymentMethodSettingsForm(), array('settings' => $settings));
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                /** @var \CSBill\PaymentBundle\Entity\PaymentMethod $data */
                $data = $event->getData();

                $formModifier($event->getForm(), $data->getPaymentMethod());
            }
        );

        $builder->get('payment_method')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $method = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback functions!
                $formModifier($event->getForm()->getParent(), $method);
            }
        );
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'manager'
            )
        );
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'payment_methods';
    }
}
