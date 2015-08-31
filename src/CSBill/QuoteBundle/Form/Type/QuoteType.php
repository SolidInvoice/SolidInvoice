<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Form\Type;

use CSBill\TaxBundle\Repository\TaxRepository;
use CSBill\QuoteBundle\Form\EventListener\QuoteUsersSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteType extends AbstractType
{
    /**
     * @var TaxRepository
     */
    private $repo;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->repo = $entityManager->getRepository('CSBillTaxBundle:Tax');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'client',
            null,
            array(
                'attr' => array(
                    'class' => 'select2',
                ),
                'placeholder' => 'quote.client.choose',
            )
        );

        $builder->add('discount', 'percent', array('required' => false));

        $builder->add(
            'items',
            'collection',
            array(
                'type' => 'quote_item',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
            )
        );

        $builder->add('terms');
        $builder->add('notes', null, array('help' => 'Notes will not be visible to the client'));
        $builder->add('total', 'hidden_money');
        $builder->add('baseTotal', 'hidden_money');
        $builder->add('tax', 'hidden_money');

        $builder->addEventSubscriber(new QuoteUsersSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'quote';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CSBill\QuoteBundle\Entity\Quote',
            )
        );
    }
}
