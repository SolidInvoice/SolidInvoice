<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Form\Type;

use CSBill\MoneyBundle\Form\DataTransformer\ModelTransformer;
use CSBill\MoneyBundle\Form\DataTransformer\ViewTransformer;
use CSBill\MoneyBundle\Currency;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HiddenMoneyType extends AbstractType
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency $currency
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
	$currency = $this->currency->getCurrency();
        $builder
	    ->addViewTransformer(new ViewTransformer($currency), true)
	    ->addModelTransformer(new ModelTransformer($currency), true);
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
