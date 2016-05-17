<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Event;

use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Component\EventDispatcher\Event;

class QuoteEvent extends Event
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @param Quote $quote
     */
    public function __construct(Quote $quote = null)
    {
        $this->quote = $quote;
    }

    /**
     * @param Quote $quote
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
    }

    /**
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }
}
