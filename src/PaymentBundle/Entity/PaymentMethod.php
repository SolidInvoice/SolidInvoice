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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payment_methods")
 * @ORM\Entity(repositoryClass="SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository")
 * @UniqueEntity("gatewayName")
 */
class PaymentMethod implements GatewayConfigInterface
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @var UuidInterface
     *
     * @ORM\Column(name="id", type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=125)
     * @Assert\NotBlank
     * @Serialize\Groups({"payment_api"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="gateway_name", type="string", length=125, unique=true)
     */
    private $gatewayName;

    /**
     * @var string|null
     *
     * @ORM\Column(name="factory", type="string", length=125)
     */
    private $factoryName;

    /**
     * @var array
     *
     * @ORM\Column(name="config", type="array", nullable=true)
     */
    private $config;

    /**
     * @ORM\Column(name="internal", type="boolean", nullable=true)
     *
     * @var bool|null
     */
    private $internal = false;

    /**
     * @ORM\Column(name="enabled", type="boolean",  nullable=true)
     *
     * @var bool|null
     */
    private $enabled;

    /**
     * @var Collection<int, Payment>
     *
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="method", cascade={"persist"})
     */
    private $payments;

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

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
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

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get settings.
     *
     * @return array
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

    /**
     * Add payment.
     */
    public function addPayment(Payment $payment): self
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Removes a payment.
     */
    public function removePayment(Payment $payment): self
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments.
     *
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

    public function setFactoryName($factory): self
    {
        $this->factoryName = $factory;

        return $this;
    }

    /**
     * Return the payment method name as a string.
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
