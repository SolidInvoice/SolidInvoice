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

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ClientBundle\Repository\CreditRepository;
use SolidInvoice\CoreBundle\Doctrine\Type\BigIntegerType;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: Credit::TABLE_NAME, uniqueConstraints: [new ORM\UniqueConstraint(columns: ['client_id'])])]
#[ORM\Entity(repositoryClass: CreditRepository::class)]
#[ApiResource(
    uriTemplate: '/clients/{clientId}/credit',
    operations: [new Get(), new Patch()],
    uriVariables: [
        'clientId' => new Link(
            fromProperty: 'credit',
            fromClass: Client::class,
        ),
    ],
    normalizationContext: [
        'groups' => ['credit_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['credit_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
class Credit implements Stringable
{
    final public const TABLE_NAME = 'client_credit';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['credit_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(name: 'value_amount', type: BigIntegerType::NAME)]
    #[Groups(['credit_api:read', 'credit_api:write'])]
    private BigNumber $value;

    #[ORM\OneToOne(inversedBy: 'credit', targetEntity: Client::class)]
    #[ORM\JoinColumn(name: 'client_id', referencedColumnName: 'id', unique: true)]
    private ?Client $client = null;

    public function __construct()
    {
        $this->value = BigInteger::zero();
    }

    public function getId(): Ulid
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

    public function getValue(): BigNumber
    {
        return $this->value;
    }

    /**
     * @throws MathException
     */
    public function setValue(BigNumber | float | int | string $value): self
    {
        $this->value = BigNumber::of($value);

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->value->toInt();
    }
}
