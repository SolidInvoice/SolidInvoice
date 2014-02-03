<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Exception;

class InvalidStepException extends \Exception
{
    public function __construct($step)
    {
        $type = gettype($step);

        if ($type === 'object') {
            $type = 'instance of ' . get_class($step);
        }

        parent::__construct(sprintf('Expected type of AbstractStep, %s given', $type));
    }
}
