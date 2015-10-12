<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Entity;

use APY\DataGridBundle\Grid\Mapping as Grid;
use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serialize;

/**
 * @ORM\Table(name="tax_rates")
 * @ORM\Entity(repositoryClass="CSBill\TaxBundle\Repository\TaxRepository")
 * @UniqueEntity("name")
 * @Gedmo\Loggable()
 * @Serialize\ExclusionPolicy("all")
 */
class Tax
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    const TYPE_INCLUSIVE = 'inclusive';
    const TYPE_EXCLUSIVE = 'exclusive';

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
     * @Serialize\Expose()
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="rate", type="float", precision=4)
     * @Grid\Column(type="percent")
     * @Assert\Type("float")
     * @Assert\NotBlank()
     * @Serialize\Expose()
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_type", type="string", length=32)
     * @Assert\NotBlank()
     * @Serialize\Expose()
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="CSBill\InvoiceBundle\Entity\Item", mappedBy="tax")
     */
    private $invoiceItems;

    /**
     * @ORM\OneToMany(targetEntity="CSBill\QuoteBundle\Entity\Item", mappedBy="tax")
     */
    private $quoteItems;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->quoteItems = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Tax
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * @param string $rate
     *
     * @return Tax
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Tax
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     *
     * @static
     */
    public static function getTypes()
    {
        $types = array(
            self::TYPE_INCLUSIVE,
            self::TYPE_EXCLUSIVE,
        );

        return array_combine($types, $types);
    }

    /**
     * @return ArrayCollection
     */
    public function getInvoiceItems()
    {
        return $this->invoiceItems;
    }

    /**
     * @param array $invoiceItems
     *
     * @return Tax
     */
    public function setInvoiceItems(array $invoiceItems)
    {
        $this->invoiceItems = $invoiceItems;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQuoteItems()
    {
        return $this->quoteItems;
    }

    /**
     * @param array $quoteItems
     *
     * @return Tax
     */
    public function setQuoteItems(array $quoteItems)
    {
        $this->quoteItems = $quoteItems;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $type = $this->type === self::TYPE_INCLUSIVE ? 'inc' : 'exl';
        $rate = $this->rate * 100;

        return "{$rate}% {$this->name} ({$type})";
    }
}
