<?php

namespace CSBill\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Tax
 *
 * @ORM\Table(name="tax_rates")
 * @ORM\Entity(repositoryClass="CSBill\CoreBundle\Repository\TaxRepository")
 * @Gedmo\SoftDeleteable(fieldName="deleted")
 * @UniqueEntity(fields={"name"})
 */
class Tax
{
    const TYPE_INCLUSIVE = 'inclusive';

    const TYPE_EXCLUSIVE = 'exclusive';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=32)
     * @var string
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @ORM\Column(name="rate", type="float", precision=4)
     * @var string
     * @Assert\Type("float")
     */
    private $rate;

    /**
     * @ORM\Column(name="tax_type", type="string", length=32)
     * @var string
     * @Assert\NotBlank
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

    /**
     * @var \DateTIme $created
     *
     * @ORM\Column(name="created", type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Assert\DateTime()
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @ORM\Column(name="updated", type="datetime")
     * @Gedmo\Timestampable(on="update")
     * @Assert\DateTime()
     */
    private $updated;

    /**
     * @var \DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $deleted;

    public function __construct()
    {
        $this->invoiceItems = new ArrayCollection();
        $this->quoteItems = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
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
     * @return \DateTIme
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTIme $created
     *
     * @return Tax
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @param \DateTime $updated
     *
     * @return Tax
     */
    public function setUpdated(\DateTime $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param \DateTime $deleted
     *
     * @return Tax
     */
    public function setDeleted(\DateTime $deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $type = $this->type === 'inclusive' ? 'inc' : 'exl';

        return "{$this->rate}% {$this->name} ({$type})";
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
            self::TYPE_EXCLUSIVE
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
}
