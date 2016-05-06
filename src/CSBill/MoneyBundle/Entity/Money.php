<?php

namespace CSBill\MoneyBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Money\Currency;
use Money\Money as MoneyObject;

/**
 * @Embeddable
 */
class Money
{
    /**
     * @var string
     */
    private static $baseCurrency;

    /**
     * @Column(type="integer", name="amount", nullable=true)
     */
    private $amount;

    /**
     * @Column(type="string", name="currency", nullable=true, length=3)
     */
    private $currency;

    /**
     * @param string $baseCurrency
     */
    public static function setBaseCurrency($baseCurrency)
    {
        self::$baseCurrency = $baseCurrency;
    }

    /**
     * @return string
     */
    public static function getBaseCurrency()
    {
        return self::$baseCurrency;
    }

    /**
     * @return MoneyObject
     */
    public function getMoney()
    {
        return new MoneyObject($this->amount ?: 0, new Currency($this->currency ?: self::$baseCurrency));
    }

    /**
     * @param MoneyObject $money
     */
    public function setMoney(MoneyObject $money)
    {
        $this->amount = $money->getAmount();
        $this->currency = $money->getCurrency()->getName();
    }
}