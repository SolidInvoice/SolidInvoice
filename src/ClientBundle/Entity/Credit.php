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

namespace SolidInvoice\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;
use SolidInvoice\CoreBundle\Doctrine\Id\IdGenerator;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;

/**
 * SolidInvoice\ClientBundle\Entity\Credit.
 *
 * @ORM\Table(name="client_credit")
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\CreditRepository")
 * @Gedmo\Loggable
 */
class Credit
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=IdGenerator::class)
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     *
     * @var MoneyEntity
     */
    private $value;

    /**
     * @var Client|null
     * @ORM\OneToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="credit")
     */
    private $client;

    public function __construct()
    {
        $this->value = new MoneyEntity();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Client
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getValue(): Money
    {
        return $this->value->getMoney();
    }

    /**
     * @return $this|Credit
     */
    public function setValue(Money $value): self
    {
        $this->value = new MoneyEntity($value);

        return $this;
    }

    public function __toString(): string
    {
        return $this->value->getMoney()->getAmount();
    }
}
