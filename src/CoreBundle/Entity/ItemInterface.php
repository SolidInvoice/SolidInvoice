<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Entity;

use CSBill\TaxBundle\Entity\Tax;
use Money\Money;

interface ItemInterface
{
    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return ItemInterface
     */
    public function setDescription(string $description): ItemInterface;

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string;

    /**
     * Set the price.
     *
     * @param Money $price
     *
     * @return ItemInterface
     */
    public function setPrice(Money $price): ItemInterface;

    /**
     * Get the price.
     *
     * @return Money
     */
    public function getPrice(): Money;

    /**
     * Set the qty.
     *
     * @param float $qty
     *
     * @return ItemInterface
     */
    public function setQty(float $qty): ItemInterface;

    /**
     * Get qty.
     *
     * @return float
     */
    public function getQty(): ?float;

    /**
     * @param Money $total
     *
     * @return ItemInterface
     */
    public function setTotal(Money $total): ItemInterface;

    /**
     * Get the line item total.
     *
     * @return Money
     */
    public function getTotal(): Money;

    /**
     * @return Tax
     */
    public function getTax(): Tax;

    /**
     * @param Tax $tax
     *
     * @return ItemInterface
     */
    public function setTax(Tax $tax = null): ItemInterface;
}
