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

namespace SolidInvoice\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Symfony\Component\Serializer\Annotation as Serialize;

#[ORM\Embeddable]
class Discount
{
    final public const TYPE_PERCENTAGE = 'percentage';

    final public const TYPE_MONEY = 'money';

    #[ORM\Embedded(class: MoneyEntity::class)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private MoneyEntity $valueMoney;

    #[ORM\Column(name: 'value_percentage', type: 'float', nullable: true)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private ?float $valuePercentage = null;

    #[ORM\Column(name: 'type', type: 'string', nullable: true)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private ?string $type = self::TYPE_PERCENTAGE;

    public function __construct()
    {
        $this->valueMoney = new MoneyEntity();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getValueMoney(): ?MoneyEntity
    {
        return $this->valueMoney;
    }

    public function setValueMoney(MoneyEntity $valueMoney): self
    {
        $this->valueMoney = $valueMoney;

        return $this;
    }

    public function getValuePercentage(): ?float
    {
        return $this->valuePercentage;
    }

    public function setValuePercentage(float $valuePercentage): self
    {
        $this->valuePercentage = $valuePercentage;

        return $this;
    }

    public function getValue(): float|\Money\Money|null
    {
        return match ($this->getType()) {
            self::TYPE_PERCENTAGE => $this->getValuePercentage(),
            self::TYPE_MONEY => $this->getValueMoney() instanceof \SolidInvoice\MoneyBundle\Entity\Money ? $this->getValueMoney()->getMoney() : null,
            default => null,
        };
    }

    public function setValue(float|\Money\Money|null $value): void
    {
        switch ($this->getType()) {
            case self::TYPE_PERCENTAGE:
                $this->setValuePercentage((float) $value);

                break;

            case self::TYPE_MONEY:
                $this->setValueMoney(new MoneyEntity(new Money(((int) $value) * 100, new Currency('USD'))));

                break;
        }
    }
}
