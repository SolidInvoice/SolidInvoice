<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="payment_methods")
 * @ORM\Entity(repositoryClass="CSBill\PaymentBundle\Repository\PaymentMethod")
 * @Gedmo\SoftDeleteable()
 * @Gedmo\Loggable()
 */
class PaymentMethod
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=125)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="payment_method", type="string", length=125)
     */
    private $paymentMethod;

    /**
     * @var array
     *
     * @ORM\Column(name="settings", type="array", nullable=true)
     */
    private $settings;

    /**
     * @var Status $status
     *
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="paymentMethods")
     */
    private $defaultStatus;

    /**
     * @ORM\Column(name="public", type="boolean", nullable=true)
     *
     * @var bool
     */
    private $public;

    /**
     * @ORM\Column(name="enabled", type="boolean",  nullable=true)
     *
     * @var bool
     */
    private $enabled;

    /**
     * @var ArrayCollection $payments
     *
     * @ORM\OneToMany(
     *     targetEntity="CSBill\PaymentBundle\Entity\Payment",
     *     mappedBy="method",
     *     cascade={"persist"}
     * )
     */
    private $payments;

    public function __construct()
    {
        $this->payments = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     *
     * @return PaymentMethod
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @param string $paymentMethod
     *
     * @return PaymentMethod
     */
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Set settings
     *
     * @param array $settings
     *
     * @return PaymentMethod
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Set status
     *
     * @param  Status $status
     * @return $this
     */
    public function setDefaultStatus(Status $status)
    {
        $this->defaultStatus = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return Status
     */
    public function getDefaultStatus()
    {
        return $this->defaultStatus;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return (bool) $this->public;
    }

    /**
     * @param bool $public
     *
     * @return $this
     */
    public function setPublic($public)
    {
        $this->public = (bool) $public;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;

        return $this;
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->enabled = false;

        return $this;
    }

    /**
     * Add payment
     *
     * @param  PaymentDetails $payment
     * @return $this
     */
    public function addPayment(PaymentDetails $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Removes a payment
     *
     * @param PaymentDetails $payment
     *
     * @return $this
     */
    public function removePayment(PaymentDetails $payment)
    {
        $this->payments->removeElement($payment);

        return $this;
    }

    /**
     * Get payments
     *
     * @return ArrayCollection
     */
    public function getPayments()
    {
        return $this->payments;
    }
    /**
     * Return the payment method name as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

}
