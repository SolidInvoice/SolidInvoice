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

namespace SolidInvoice\CoreBundle\Currency;

use SolidInvoice\CoreBundle\Contracts\HasFrozenCurrencyInterface;
use WeakMap;

/**
 * Sanctions a single document instance to clear its frozen `currencyCode`.
 * {@see \SolidInvoice\CoreBundle\Listener\FreezeCurrencyListener} sanctions a document
 * when a workflow transition returns it to Draft; {@see \SolidInvoice\CoreBundle\Listener\Doctrine\FrozenCurrencyGuardListener}
 * consumes the sanction to allow that one `non-null → NULL` write, and rejects every
 * other write to the column. `WeakMap` keys do not keep the entity alive, so a
 * sanction that is never consumed needs no cleanup.
 */
final class FrozenCurrencyThaw
{
    /** @var WeakMap<HasFrozenCurrencyInterface, true> */
    private WeakMap $sanctioned;

    public function __construct()
    {
        $this->sanctioned = new WeakMap();
    }

    public function sanction(HasFrozenCurrencyInterface $document): void
    {
        $this->sanctioned[$document] = true;
    }

    /**
     * Returns true once, then forgets the document.
     */
    public function consume(HasFrozenCurrencyInterface $document): bool
    {
        if (! isset($this->sanctioned[$document])) {
            return false;
        }

        unset($this->sanctioned[$document]);

        return true;
    }
}
