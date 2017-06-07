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

namespace CSBill\MoneyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money as BaseMoney;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Embeddable()
 */
class Money
{
    /**
     * @ORM\Column(name="amount", type="integer")
     * @Serialize\Groups({"api", "js"})
     *
     * @var int
     */
    private $value = 0;

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     *
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private static $baseCurrency;

    /**
     * @param $currency
     */
    public static function setBaseCurrency($currency)
    {
        self::$baseCurrency = $currency;
    }

    /**
     * @return string
     */
    public static function getBaseCurrency(): string
    {
        return self::$baseCurrency;
    }

    /**
     * @param BaseMoney $money
     */
    public function __construct(?BaseMoney $money = null)
    {
        if ($money) {
            $this->value = $money->getAmount();
            $this->currency = $money->getCurrency()->getCode();
        }
    }

    /**
     * @return BaseMoney
     */
    public function getMoney(): BaseMoney
    {
        return new BaseMoney((int) $this->value, new Currency($this->currency ?: self::$baseCurrency));
    }
}
