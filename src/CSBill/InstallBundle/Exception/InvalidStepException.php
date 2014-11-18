<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Exception;

class InvalidStepException extends \Exception
{
    public function __construct($step)
    {
        $type = gettype($step);

        if ($type === 'object') {
            $type = 'instance of '.get_class($step);
        }

        parent::__construct(sprintf('Expected type of AbstractStep, %s given', $type));
    }
}
