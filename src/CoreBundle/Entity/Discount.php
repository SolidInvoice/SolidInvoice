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

namespace SolidInvoice\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Symfony\Component\Serializer\Annotation as Serialize;

/**
 * @ORM\Embeddable()
 */
class Discount
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_MONEY = 'money';

    /**
     * @var MoneyEntity
     *
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     * @Serialize\Groups({"invoice_api", "quote_api", "client_api"})
     */
    private $valueMoney;

    /**
     * @var float
     *
     * @ORM\Column(name="value_percentage", type="float", nullable=true)
     * @Serialize\Groups({"invoice_api", "quote_api", "client_api"})
     */
    private $valuePercentage;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=true)
     * @Serialize\Groups({"invoice_api", "quote_api", "client_api"})
     */
    private $type;

    public function __construct()
    {
        $this->type = self::TYPE_PERCENTAGE;
        $this->valueMoney = new MoneyEntity();
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return MoneyEntity
     */
    public function getValueMoney(): ?MoneyEntity
    {
        return $this->valueMoney;
    }

    /**
     * @param MoneyEntity $valueMoney
     *
     * @return $this
     */
    public function setValueMoney(MoneyEntity $valueMoney): self
    {
        $this->valueMoney = $valueMoney;

        return $this;
    }

    /**
     * @return float
     */
    public function getValuePercentage(): ?float
    {
        return $this->valuePercentage;
    }

    /**
     * @param float $valuePercentage
     *
     * @return $this
     */
    public function setValuePercentage(float $valuePercentage): self
    {
        $this->valuePercentage = $valuePercentage;

        return $this;
    }

    public function getValue()
    {
        switch ($this->getType()) {
            case self::TYPE_PERCENTAGE:
                return $this->getValuePercentage();

            case self::TYPE_MONEY:
                return $this->getValueMoney()->getMoney();
        }

        return null;
    }

    public function setValue($value)
    {
        switch ($this->getType()) {
            case self::TYPE_PERCENTAGE:
                $this->setValuePercentage((float) $value);

                break;

            case self::TYPE_MONEY:
                $this->setValueMoney(new MoneyEntity(new Money(((int) $value) * 100, new Currency(''))));

                break;
        }
    }
}
