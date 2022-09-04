<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Form\Type;

use SolidInvoice\ClientBundle\Form\DataTransformer\ContactTypeTransformer;
use SolidInvoice\ClientBundle\Repository\ContactTypeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\ContactDetailTypeTest
 */
class ContactDetailType extends AbstractType
{
    /**
     * @var ContactTypeRepository
     */
    private $contactTypeRepository;

    public function __construct(ContactTypeRepository $contactTypeRepository)
    {
        $this->contactTypeRepository = $contactTypeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['contactTypes'] = $this->contactTypeRepository->findAll();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            $builder
                ->create(
                    'type',
                    HiddenType::class,
                    [
                        'attr' => [
                            'class' => 'input-group-select-val',
                        ],
                    ]
                )
                ->addModelTransformer(new ContactTypeTransformer($this->contactTypeRepository->findAll()))
        );

        $builder->add(
            'value',
            TextType::class,
            [
                'constraints' => [
                    // @TODO: This constraints should not be hard-coded
                    new NotBlank(['groups' => 'not_blank']),
                    new Email(['groups' => 'email']),
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('validation_groups', function (FormInterface $form) {
            // @codeCoverageIgnoreStart
            $type = $form->get('type')->getData()->getName();
            $value = $form->get('value')->getData();

            if (!empty($type) && empty($value)) {
                return ['Default', 'not_blank'];
            }

            if ('email' === strtolower($form->get('type')->getData()->getName())) {
                return ['Default', 'email'];
            }
            // @codeCoverageIgnoreEnd
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_detail';
    }
}
