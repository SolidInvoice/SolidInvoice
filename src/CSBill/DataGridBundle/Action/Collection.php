<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Action;

use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function add($value)
    {
        if (!$value instanceof ActionColumn) {
            $type = is_object($value) ? 'instance of '.get_class($value) : gettype($value);
            throw new \Exception(sprintf('Instance of ActionColumn expected, %s given', $type));
        }

        return parent::add($value);
    }
}
