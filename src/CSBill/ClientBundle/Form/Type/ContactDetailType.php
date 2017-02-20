<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form\Type;

use CSBill\ClientBundle\Form\DataTransformer\ContactTypeTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactDetailType extends AbstractType
{
    /**
     * @var \CSBill\ClientBundle\Entity\ContactType[]
     */
    protected $types;

    /**
     * @param \CSBill\ClientBundle\Entity\ContactType[] $types
     */
    public function __construct(array $types)
    {
	$this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
	$view->vars['contactTypes'] = $this->types;
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
		->addModelTransformer(new ContactTypeTransformer($this->types))
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
	    $type = $form->get('type')->getData()->getName();
	    $value = $form->get('value')->getData();

	    if (!empty($type) && empty($value)) {
		return ['Default', 'not_blank'];
	    }

	    switch (strtolower($form->get('type')->getData()->getName())) {
		case 'email':
		    return ['Default', 'email'];
		    break;
	    }
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
