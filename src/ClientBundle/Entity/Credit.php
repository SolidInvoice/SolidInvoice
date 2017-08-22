<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Entity;

use SolidInvoice\CoreBundle\Traits\Entity;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Money\Money;

/**
 * SolidInvoice\ClientBundle\Entity\Credit.
 *
 * @ORM\Table(name="client_credit")
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\CreditRepository")
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
     */
    private $id;

    /**
     * @ORM\Embedded(class="SolidInvoice\MoneyBundle\Entity\Money")
     *
     * @var MoneyEntity
     */
    private $value;

    /**
     * @var Client
     * @ORM\OneToOne(targetEntity="SolidInvoice\ClientBundle\Entity\Client", inversedBy="credit")
     */
    private $client;

    public function __construct()
    {
        $this->value = new MoneyEntity();
    }

    /**
     * @return int|null
     */
    public function getId(): ? int
    {
        return $this->id;
    }

    /**
     * @return Client
     */
    public function getClient(): ? Client
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
