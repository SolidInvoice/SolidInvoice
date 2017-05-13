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

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use CSBill\MoneyBundle\Entity\Money as MoneyEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serialize;
use Money\Money;

/**
 * CSBill\ClientBundle\Entity\Credit.
 *
 * @ORM\Table(name="client_credit")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\CreditRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Credit
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serialize\Groups({"noneg"})
     */
    private $id;

    /**
     * @ORM\Embedded(class="CSBill\MoneyBundle\Entity\Money")
     *
     * @var MoneyEntity
     *
     * @Serialize\Groups({"api", "js"})
     * @Serialize\Inline()
     */
    private $value;

    /**
     * @var Client
     * @ORM\OneToOne(targetEntity="CSBill\ClientBundle\Entity\Client", inversedBy="credit")
     * @Serialize\Groups({"js"})
     */
    private $client;

    public function __construct()
    {
        $this->value = new MoneyEntity();
    }

    /**
     * @return int|null
     */
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

    /**
     * @param Client $client
     *
     * @return Credit
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Money
     */
    public function getValue(): Money
    {
        return $this->value->getMoney();
    }

    /**
     * @param Money $value
     *
     * @return $this|Credit
     */
    public function setValue(Money $value): self
    {
        $this->value = new MoneyEntity($value);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value->getMoney()->getAmount();
    }
}
