<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\CoreBundle\Form\Extension;

use CSBill\CoreBundle\Util\Currency;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MoneyExtension extends AbstractTypeExtension
{
    /**
     * @var string
     */
    protected $currency;

    /**
     * @param string $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'currency' => $this->currency->getCurrency(),
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'money';
    }
}
