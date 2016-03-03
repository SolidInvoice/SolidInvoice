<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Doctrine\Hydrator;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Money\Currency;
use Money\Money;

class MoneyHydrator extends AbstractHydrator
{
    /**
     * @var Currency
     */
    private static $currency;

    /**
     * @param Currency $currency
     */
    public static function setCurrency($currency)
    {
	if (null !== $currency) {
	    if (!$currency instanceof Currency) {
		$currency = new Currency($currency);
	    }

	    self::$currency = $currency;
	}
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $result = [];
        $cache = [];
        foreach ($this->_stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
	    $this->hydrateRowData($row, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateRowData(array $data, array &$result)
    {
	if (0 === count($data)) {
            return false;
        }

	$keys = array_keys($data);

        // Assume first column is id field
        /** @var string $id */
        $id = $keys[0];

	if (2 === count($data)) {
            // If only one more field assume that this is the value field
            /** @var string $key1 */
            $key1 = $keys[1];
	    $value = $data[$key1];
	} elseif (1 === count($data)) {
            // Remove ID field and add remaining fields as value array
	    $value = array_shift($data);
        } else {
            throw new \Exception('When hydrating as "money", you cannot return more than 2 values (first one for the id, and second one for the value, or only one for the value)');
        }

        $result[$id] = new Money((int) $value, self::$currency);
    }
}
