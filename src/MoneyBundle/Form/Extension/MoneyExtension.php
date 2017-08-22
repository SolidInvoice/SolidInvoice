<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Form\Extension;

use SolidInvoice\MoneyBundle\Form\DataTransformer\ModelTransformer;
use SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer;
use Money\Currency;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyExtension extends AbstractTypeExtension
{
    /**
     * @var Currency
     */
    protected $currency;

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
        $builder
            ->addViewTransformer(new ViewTransformer($options['currency']), true)
            ->addModelTransformer(new ModelTransformer($options['currency']), true);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault(
            'currency',
            $this->currency->getCode()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return MoneyType::class;
    }
}
