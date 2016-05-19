<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Form\Type;

use CSBill\MoneyBundle\Form\DataTransformer\ModelTransformer;
use CSBill\MoneyBundle\Form\DataTransformer\ViewTransformer;
use Money\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HiddenMoneyType extends AbstractType
{
    /**
     * @var \Money\Currency
     */
    private $currency;

    /**
     * @param \Money\Currency $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addViewTransformer(new ViewTransformer($this->currency), true)
            ->addModelTransformer(new ModelTransformer($this->currency), true);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'hidden_money';
    }
}
