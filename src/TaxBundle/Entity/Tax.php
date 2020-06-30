<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SolidInvoice\CoreBundle\Entity\ItemInterface;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="tax_rates")
 * @ORM\Entity(repositoryClass="SolidInvoice\TaxBundle\Repository\TaxRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable
 */
class Tax
{
    use TimeStampable;

    const TYPE_INCLUSIVE = 'Inclusive';

    const TYPE_EXCLUSIVE = 'Exclusive';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="rate", type="float", precision=4)
     * @Assert\Type("float")
     * @Assert\NotBlank()
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_type", type="string", length=32)
     * @Assert\NotBlank()
     */
    private $type;

    /**
     * @var Collection|ItemInterface[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\InvoiceBundle\Entity\Item", mappedBy="tax")
     */
    private $invoiceItems;

    /**
     * @var Collection|ItemInterface[]
     *
     * @ORM\OneToMany(targetEntity="SolidInvoice\QuoteBundle\Entity\Item", mappedBy="tax")
     */
    private $quoteItems;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->quoteItems = new ArrayCollection();
    }

    /**
     * @static
     */
    public static function getTypes(): array
    {
        $types = [
            self::TYPE_INCLUSIVE,
            self::TYPE_EXCLUSIVE,
        ];

        return array_combine($types, $types);
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return Tax
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return float
     */
    public function getRate(): ?float
    {
        return $this->rate;
    }

    /**
     * @return Tax
     */
    public function setRate(float $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return Tax
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection|ItemInterface[]
     */
    public function getInvoiceItems(): Collection
    {
        return $this->invoiceItems;
    }

    /**
     * @param ItemInterface[] $invoiceItems
     *
     * @return Tax
     */
    public function setInvoiceItems(array $invoiceItems): self
    {
        $this->invoiceItems = $invoiceItems;

        return $this;
    }

    /**
     * @return Collection|ItemInterface[]
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }

    /**
     * @param ItemInterface[] $quoteItems
     *
     * @return Tax
     */
    public function setQuoteItems(array $quoteItems): self
    {
        $this->quoteItems = $quoteItems;

        return $this;
    }

    public function __toString(): string
    {
        $type = self::TYPE_INCLUSIVE === $this->type ? 'inc' : 'exl';

        return "{$this->rate}% {$this->name} ({$type})";
    }
}
