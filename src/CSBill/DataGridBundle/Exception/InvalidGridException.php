<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Exception;

use Exception;

class InvalidGridException extends \Exception
{
    /**
     * @param string $grid
     */
    public function __construct($grid)
    {
	$message = sprintf('The grid "%s" does not exist.', $grid);

	parent::__construct($message);
    }
}