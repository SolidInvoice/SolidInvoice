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

use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use Doctrine\ORM\Mapping as ORM;
use Money\Money;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use Stringable;

#[ORM\Table(name: Credit::TABLE_NAME)]
#[ORM\Entity(repositoryClass: CreditRepository::class)]
class Credit implements Stringable
{
    final public const TABLE_NAME = 'client_credit';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UuidBinaryOrderedTimeType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Embedded(class: MoneyEntity::class)]
    private MoneyEntity $value;

    #[ORM\OneToOne(inversedBy: 'credit', targetEntity: Client::class)]
    private ?Client $client = null;

    public function __construct()
    {
        $this->value = new MoneyEntity();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

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
