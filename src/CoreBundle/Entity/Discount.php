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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use Symfony\Component\Serializer\Annotation as Serialize;

#[ORM\Embeddable]
class Discount
{
    final public const TYPE_PERCENTAGE = 'percentage';

    final public const TYPE_MONEY = 'money';

    #[ORM\Column(name: 'valueMoney_amount', type: BigIntegerType::NAME)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private BigInteger $valueMoney;

    #[ORM\Column(name: 'value_percentage', type: Types::FLOAT, nullable: true)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private ?float $valuePercentage = null;

    #[ORM\Column(name: 'type', type: Types::STRING, nullable: true)]
    #[Serialize\Groups(['invoice_api', 'quote_api', 'client_api'])]
    private ?string $type = self::TYPE_PERCENTAGE;

    public function __construct()
    {
        $this->valueMoney = BigInteger::zero();
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

    public function getValueMoney(): BigInteger
    {
        return $this->valueMoney;
    }

    /**
     * @throws MathException
     */
    public function setValueMoney(BigInteger|float|int|string $valueMoney): self
    {
        $this->valueMoney = BigInteger::of($valueMoney);

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

    public function getValue(): float | BigInteger
    {
        return match ($this->getType()) {
            self::TYPE_PERCENTAGE => $this->getValuePercentage() ?? 0.0,
            self::TYPE_MONEY => $this->getValueMoney(),
            default => BigInteger::zero(),
        };
    }

    /**
     * @throws MathException
     */
    public function setValue(float | BigInteger $value): void
    {
        switch ($this->getType()) {
            case self::TYPE_PERCENTAGE:
                $this->setValuePercentage((float) $value);
                break;

            case self::TYPE_MONEY:
                $this->setValueMoney($value);
                break;
        }
    }
}
