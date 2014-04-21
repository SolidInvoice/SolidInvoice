<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Form;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;
use CSBill\ClientBundle\Entity\ContactType;
use CSBill\ClientBundle\Form\DataTransformer\ContactTypeTransformer;

class ContactDetail extends AbstractType
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    /**
     * @var \CSBill\ClientBundle\Entity\ContactType
     */
    private $type;

    /**
     * @param EntityManager $entityManager
     * @param ContactType   $type
     */
    public function __construct(EntityManager $entityManager, ContactType $type)
    {
        $this->entityManager = $entityManager;
        $this->type = $type;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new ContactTypeTransformer($this->entityManager);

        $options = array(
                        'required' => $this->type->isRequired(),
                        'data' => $this->type,
                        'data_class' => null
                        );

        $contstraints = $this->buildConstraints();

        if ($this->type->isRequired()) {
            $contstraints[] = new Constraints\NotBlank();
        }

        $builder->add(
            $builder->create('type', 'hidden', $options)
                ->addModelTransformer($transformer)
        );

        $builder->add('value', $this->type->getType(), array(
            'label' => $this->humanize($this->type->getName()),
            'constraints' => $contstraints
        ));
    }

    public function getName()
    {
        return 'contact_detail';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'CSBill\ClientBundle\Entity\ContactDetail'
        ));
    }

    /**
     * @param  string $text
     * @return string
     */
    private function humanize($text)
    {
        return ucwords(str_replace('_', ' ', $text));
    }

    /**
     * @return array
     */
    private function buildConstraints()
    {
        $options = $this->type->getOptions();

        $constraints = array();

        if (is_array($options) && array_key_exists('constraints', $options)) {
            foreach ($options['constraints'] as $constraint) {
                $constraint = str_replace(' ', '', $this->humanize($constraint));
                if (class_exists($class = sprintf('Symfony\Component\Validator\Constraints\\%s', $constraint))) {
                    $constraints[] = new $class;
                }
            }
        }

        return $constraints;
    }
}
