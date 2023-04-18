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

namespace SolidInvoice\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Payum\Core\Model\GatewayConfigInterface;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payment_methods")
 * @ORM\Entity(repositoryClass="SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository")
 * @UniqueEntity("gatewayName")
 */
class PaymentMethod implements GatewayConfigInterface, Stringable
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     */
    private ?UuidInterface $id = null;

    /**
     * @ORM\Column(name="name", type="string", length=125)
     * @Assert\NotBlank
     * @Serialize\Groups({"payment_api"})
     */
    private ?string $name = null;

    /**
     * @ORM\Column(name="gateway_name", type="string", length=125)
     */
    private ?string $gatewayName = null;

    /**
     * @ORM\Column(name="factory", type="string", length=125)
     */
    private ?string $factoryName = null;

    /**
     * @var array<string, string>
     *
     * @ORM\Column(name="config", type="array", nullable=true)
     */
    private array $config = [];

    /**
     * @ORM\Column(name="internal", type="boolean", nullable=true)
     */
    private bool $internal = false;

    /**
     * @ORM\Column(name="enabled", type="boolean",  nullable=true)
     */
    private bool $enabled;

    /**
     * @var Collection<int, Payment>
     *
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="method", cascade={"persist"})
     */
    private Collection $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
        $this->disable();
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getGatewayName(): ?string
    {
        return $this->gatewayName;
    }

    /**
     * @param string $gatewayName
     */
    public function setGatewayName($gatewayName): self
    {
        $this->gatewayName = $gatewayName;

        return $this;
    }

    /**
     * @param array<string, string> $config
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return ?array<string, string|null>
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function setInternal(bool $internal): self
    {
        $this->internal = $internal;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function enable(): self
    {
        $this->enabled = true;

        return $this;
    }

    public function disable(): self
    {
        $this->enabled = false;

        return $this;
    }

    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;

        return $this;
    }

    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function getFactoryName(): ?string
    {
        return $this->factoryName;
    }

    /**
     * @param string $name
     */
    public function setFactoryName($name): self
    {
        $this->factoryName = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
