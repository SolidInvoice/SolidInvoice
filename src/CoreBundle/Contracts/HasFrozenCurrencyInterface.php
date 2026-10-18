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

namespace SolidInvoice\CoreBundle\Contracts;

use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;

/**
 * A billing document (invoice or quote) whose currency is frozen the moment it is
 * issued, so a later change to the client's currency cannot restate it.
 * {@see \SolidInvoice\CoreBundle\Traits\Entity\HasFrozenCurrency} provides the mapped
 * column and the three methods below; the implementing entity supplies
 * {@see self::getClient()}.
 */
interface HasFrozenCurrencyInterface
{
    public function getClient(): ?Client;

    public function getCurrencyCode(): ?string;

    public function setCurrencyCode(?string $currencyCode): self;

    public function getCurrency(): Currency;
}
