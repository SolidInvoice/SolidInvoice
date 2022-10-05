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

namespace SolidInvoice\MoneyBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money as BaseMoney;
use SolidInvoice\MoneyBundle\Factory\CurrencyFactory;

/**
 * @ORM\Embeddable()
 */
#[ORM\Embeddable()]
class Money
{
    /**
     * @ORM\Column(name="amount", type="integer", nullable=true)
     */
    private ?string $value = '0';

    /**
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private ?string $currency = null;

    private static string $baseCurrency = CurrencyFactory::DEFAULT_CURRENCY;

    public static function setBaseCurrency(string $currency): void
    {
        self::$baseCurrency = $currency;
    }

    public static function getBaseCurrency(): string
    {
        return self::$baseCurrency;
    }

    public function __construct(?BaseMoney $money = null)
    {
        if (null !== $money) {
            $this->value = $money->getAmount();
            $this->currency = $money->getCurrency()->getCode();
        }
    }

    public function getMoney(): BaseMoney
    {
        return new BaseMoney($this->value, new Currency($this->currency ?: self::$baseCurrency));
    }
}
