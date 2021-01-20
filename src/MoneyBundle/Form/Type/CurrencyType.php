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

namespace SolidInvoice\MoneyBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Generator;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyType extends AbstractType
{
    /**
     * @var string
     */
    private $locale;

    public function __construct(string $locale)
    {
        $this->locale = $locale;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('choices', iterator_to_array($this->getCurrencyChoices()));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return Select2Type::class;
    }

    /**
     * @return Generator
     */
    private function getCurrencyChoices()
    {
        $currencyList = Currencies::getNames($this->locale);

        $collection = (new ArrayCollection(iterator_to_array((new ISOCurrencies())->getIterator())))
            ->filter(function (Currency $currency) use ($currencyList) {
                return array_key_exists($currency->getCode(), $currencyList);
            });

        foreach ($collection as $currency) {
            if (empty($currency->getCode())) {
                continue;
            }

            yield $currencyList[$currency->getCode()] => $currency->getCode();
        }
    }
}
