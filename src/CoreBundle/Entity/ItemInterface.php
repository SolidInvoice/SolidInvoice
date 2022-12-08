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

namespace SolidInvoice\CoreBundle\Entity;

use Money\Money;
use SolidInvoice\TaxBundle\Entity\Tax;

interface ItemInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): ?int;

    public function setDescription(string $description): self;

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string;

    /**
     * Set the price.
     */
    public function setPrice(Money $price): self;

    /**
     * Get the price.
     *
     * @return Money
     */
    public function getPrice(): ?Money;

    /**
     * Set the qty.
     */
    public function setQty(float $qty): self;

    /**
     * Get qty.
     *
     * @return float
     */
    public function getQty(): ?float;

    public function setTotal(Money $total): self;

    /**
     * Get the line item total.
     *
     * @return Money
     */
    public function getTotal(): ?Money;

    /**
     * @return Tax
     */
    public function getTax(): ?Tax;

    /**
     * @param Tax $tax
     */
    public function setTax(?Tax $tax): self;
}
