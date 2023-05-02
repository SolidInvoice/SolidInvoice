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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money as BaseMoney;

#[ORM\Embeddable]
class Money
{
    #[ORM\Column(name: 'amount', type: Types::STRING, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(name: 'currency', type: Types::STRING, length: 3, nullable: true)]
    private ?string $currency = null;

    // @TODO: Ensure that a money object is always passed in
    public function __construct(?BaseMoney $money = null)
    {
        if ($money instanceof BaseMoney) {
            $this->value = $money->getAmount();
            $this->currency = $money->getCurrency()->getCode();
        }
    }

    public function getMoney(): BaseMoney
    {
        // @TODO: USD should not be hard-coded
        return new BaseMoney((int) $this->value, new Currency($this->currency ?: 'USD'));
    }
}
