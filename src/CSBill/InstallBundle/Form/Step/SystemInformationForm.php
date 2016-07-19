<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Form\Step;

use CSBill\CoreBundle\Form\Type\Select2;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Validator\Constraints;

class SystemInformationForm extends AbstractType
{
    /**
     * @var int
     */
    private $userCount;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $request
     * @param int     $userCount
     */
    public function __construct(Request $request, $userCount = 0)
    {
        $this->userCount = $userCount;
        $this->request = $request;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencies = Intl::getCurrencyBundle()->getCurrencyNames();

        if (extension_loaded('intl')) {
            $builder->add(
                'locale',
                Select2::class,
                [
                    'choices' => Intl::getLocaleBundle()->getLocaleNames(),
                    'constraints' => new Constraints\NotBlank(['message' => 'Please select a locale']),
                    'placeholder' => '',
                    'choices_as_values' => false,
                ]
            );
        } else {
            $builder->add(
                'locale',
                null,
                [
                    'data' => 'en',
                    'read_only' => true,
                    'help' => 'The only currently supported locale is "en". To choose a different locale, please install the \'intl\' extension',
                ]
            );
        }

        $builder->add(
            'currency',
            Select2::class,
            [
                'choices' => $currencies,
                'constraints' => new Constraints\NotBlank(['message' => 'Please select a currency']),
                'placeholder' => '',
                'choices_as_values' => false,
            ]
        );

        $builder->add(
            'base_url',
            null,
            [
                'constraints' => new Constraints\NotBlank(['message' => 'Please set the application base url']),
                'data' => $this->request->getSchemeAndHttpHost().$this->request->getBaseUrl(),
            ]
        );

        if (0 === $this->userCount) {
            $builder->add(
                'username',
                null,
                [
                    'constraints' => new Constraints\NotBlank(['message' => 'Please enter a username']),
                ]
            );

            $builder->add(
                'email_address',
                EmailType::class,
                [
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter a email']),
                        new Constraints\Email(),
                    ],
                ]
            );

            $builder->add(
                'password',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'options' => ['attr' => ['class' => 'password-field']],
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Repeat Password'],
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'You must enter a secure password']),
                        new Constraints\Length(['min' => 6]),
                    ],
                ]
            );
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'system_information';
    }
}
