<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\QuoteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use CSBill\CoreBundle\Entity\Status as BaseStatus;

/**
 * CSBill\QuoteBundle\Entity\Status
 *
 * @ORM\Entity
 */
class Status extends BaseStatus
{
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Quote", mappedBy="status")
     */
    protected $quotes;

    public function __construct()
    {
        $this->quotes = new ArrayCollection();
    }

    /**
     * @param Quote $quote
     *                     @return $this
     */
    public function addQuote(Quote $quote)
    {
        $this->quotes[] = $quote;
        $quote->setStatus($this);

        return $this;
    }

    /**
     * @param Quote $quote
     *                     @return $this
     */
    public function removeQuote(Quote $quote)
    {
        $this->quotes->removeElement($quote);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQuotes()
    {
        return $this->quotes;
    }
}
