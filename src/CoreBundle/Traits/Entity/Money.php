<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money as MoneyObject;

trait Money
{
    /**
     * @var int
     * @ORM\Column(name="amount_value", type="integer")
     */
    private $priceAmount;

    /**
     * @var string
     * @ORM\Column(name="amount_currency", type="string", length=64)
     */
    private $priceCurrency;

    /**
     * get price.
     *
     * @return MoneyObject
     */
    public function getAmount(): ?MoneyObject
    {
        if (!$this->priceCurrency) {
            return null;
        }

        return new MoneyObject($this->priceAmount ?: 0, new Currency($this->priceCurrency));
    }

    /**
     * Set price.
     *
     * @param MoneyObject $price
     *
     * @return $this
     */
    public function setAmount(MoneyObject $price)
    {
        $this->priceAmount = $price->getAmount();
        $this->priceCurrency = $price->getCurrency()->getName();

        return $this;
    }
}
