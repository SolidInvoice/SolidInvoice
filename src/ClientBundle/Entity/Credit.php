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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
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

    #[ORM\Column(name: 'value_amount', type: BigIntegerType::NAME)]
    private BigInteger $value;

    #[ORM\OneToOne(inversedBy: 'credit', targetEntity: Client::class)]
    private ?Client $client = null;

    public function __construct()
    {
        $this->value = BigInteger::zero();
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

    public function getValue(): BigInteger
    {
        return $this->value;
    }

    /**
     * @throws MathException
     */
    public function setValue(BigInteger|float|int|string $value): self
    {
        $this->value = BigInteger::of($value);

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->value->toInt();
    }
}
