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

namespace SolidInvoice\QuoteBundle\Event;

use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Contracts\EventDispatcher\Event;

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

    public function setQuote(Quote $quote): void
    {
        $this->quote = $quote;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }
}
