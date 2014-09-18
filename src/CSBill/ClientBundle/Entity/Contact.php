<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contacts")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ContactRepository")
 * @Gedmo\Loggable()
 * @Gedmo\SoftDeleteable()
 */
class Contact implements \serializable
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $firstname
     *
     * @ORM\Column(name="firstname", type="string", length=125, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     */
    private $firstname;

    /**
     * @var string $lastname
     *
     * @ORM\Column(name="lastname", type="string", length=125, nullable=true)
     * @Assert\Length(max=125)
     */
    private $lastname;

    /**
     * @var Client $client
     *
     * @ORM\ManyToOne(targetEntity="Client", inversedBy="contacts")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    private $client;

    /**
     * @var ArrayCollection $details
     *
     * @ORM\OneToMany(targetEntity="ContactDetail", mappedBy="contact", cascade={"persist"})
     * @Assert\Valid()
     * @Assert\Count(min=1, minMessage="You need to add at least one email address")
     */
    private $details;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->details = new ArrayCollection;
    }

    public function __set($key, $value)
    {
        if (is_array($value)) {
            foreach ($value as $element) {
                if ($element instanceof ContactDetail) {
                    $this->addDetail($element);
                }
            }
        }
    }

    public function __get($key)
    {
        if ('details_' === substr($key, 0, 8)) {

            $details = array();

            $type = substr($key, 8);

            foreach ($this->details as $detail) {
                if (strtolower((string) $detail->getType()) === strtolower($type)) {
                    $details[] = $detail;
                }
            }

            return $details;
        }

        return null;
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
     * Set firstname
     *
     * @param  string  $firstname
     * @return Contact
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param  string  $lastname
     * @return Contact
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set client
     *
     * @param  Client  $client
     * @return Contact
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get client
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Add detail
     *
     * @param  ContactDetail $detail
     * @return Contact
     */
    public function addDetail(ContactDetail $detail)
    {
        $this->details[] = $detail;
        $detail->setContact($this);

        return $this;
    }

    /**
     * Removes detail from the current contact
     *
     * @param  ContactDetail $detail
     * @return Contact
     */
    public function removeDetail(ContactDetail $detail)
    {
        $this->details->removeElement($detail);

        return $this;
    }

    /**
     * Get details
     *
     * @return ArrayCollection
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param  string             $type
     * @return null|ContactDetail
     */
    public function getDetail($type)
    {
        if (count($this->details) > 0) {
            foreach ($this->details as $detail) {
                if (strtolower((string) $detail->getType()) === strtolower($type)) {
                    return $detail;
                }
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->id, $this->firstname, $this->lastname));
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->id, $this->firstname, $this->lastname) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
