<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Entity;

use CSBill\CoreBundle\Entity\Status as BaseStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="CSBill\QuoteBundle\Repository\StatusRepository")
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
     * @param  Quote $quote
     * @return $this
     */
    public function addQuote(Quote $quote)
    {
        $this->quotes[] = $quote;
        $quote->setStatus($this);

        return $this;
    }

    /**
     * @param  Quote $quote
     * @return $this
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
