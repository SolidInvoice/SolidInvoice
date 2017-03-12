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
    public function getId();

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return ItemInterface
     */
    public function setDescription($description);

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Set the price.
     *
     * @param Money $price
     *
     * @return ItemInterface
     */
    public function setPrice(Money $price);

    /**
     * Get the price.
     *
     * @return Money
     */
    public function getPrice();

    /**
     * Set the qty.
     *
     * @param int $qty
     *
     * @return ItemInterface
     */
    public function setQty($qty);

    /**
     * Get qty.
     *
     * @return int
     */
    public function getQty();

    /**
     * @param Money $total
     *
     * @return ItemInterface
     */
    public function setTotal(Money $total);

    /**
     * Get the line item total.
     *
     * @return Money
     */
    public function getTotal();

    /**
     * @return Tax
     */
    public function getTax();

    /**
     * @param Tax $tax
     *
     * @return ItemInterface
     */
    public function setTax(Tax $tax = null);
}
