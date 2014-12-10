<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Form\Type;

use CSBill\CoreBundle\Repository\TaxRepository;
use CSBill\InvoiceBundle\Form\EventListener\InvoiceUsersSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class InvoiceType extends AbstractType
{
    /**
     * @var TaxRepository
     */
    private $repo;

    /**
     * @param TaxRepository $repo
     */
    public function __construct(TaxRepository $repo)
    {
        $this->repo = $repo;
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
                'empty_value' => 'invoice.client.choose',
            )
        );

        $builder->add('discount', 'percent');

        $builder->add(
            'items',
            'collection',
            array(
                'type' => 'invoice_item',
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            )
        );

        $builder->add('terms');
        $builder->add('notes', null, array('help' => 'Notes will not be visible to the client'));
        $builder->add('total', 'hidden');
        $builder->add('baseTotal', 'hidden');

        if ($this->repo->getTotal() > 0) {
            $builder->add('tax', 'hidden');
        }

        $builder->addEventSubscriber(new InvoiceUsersSubscriber());
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'CSBill\InvoiceBundle\Entity\Invoice',
            )
        );
    }
}
