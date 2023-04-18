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

namespace SolidInvoice\MoneyBundle\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Exception;
use Money\Currency;
use Money\Money;
use PDO;

class MoneyHydrator extends AbstractHydrator
{
    private static ?Currency $currency = null;

    public static function setCurrency(Currency $currency): void
    {
        self::$currency = $currency;
    }

    protected function hydrateAllData()
    {
        $result = [];
        foreach ($this->_stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $this->hydrateRowData($row, $result);
        }

        return $result;
    }

    protected function hydrateRowData(array $row, array &$result)
    {
        if ([] === $row) {
            return false;
        }

        $keys = array_keys($row);

        // Assume first column is id field
        /** @var string $id */
        $id = $keys[0];

        if (2 === count($row)) {
            // If only one more field assume that this is the value field
            /** @var string $key1 */
            $key1 = $keys[1];
            $value = $row[$key1];
        } elseif (1 === count($row)) {
            // Remove ID field and add remaining fields as value array
            $value = array_shift($row);
        } else {
            throw new Exception('When hydrating as "money", you cannot return more than 2 values (first one for the id, and second one for the value, or only one for the value)');
        }

        $result[$id] = new Money((int) $value, self::$currency);
    }
}
