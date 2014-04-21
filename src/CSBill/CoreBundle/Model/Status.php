<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Model;

/**
 * This class converts a status into a label for use with twitter bootstrap
 *
 * E.G it will convert 'active' into 'success', so it can be used with then class 'label-success'
 *
 * @author Pierre du Plessis
 */
abstract class Status
{
    /**
     * Contains a list of all the statuses and their corresponding labels
     *
     * @var array
     */
    protected $statusLabels = array();

    /**
     * Converts a status into a label
     *
     * @param  string $status
     * @return string
     */
    public function getStatusLabel($status)
    {
        if (isset($this->statusLabels[$status])) {
            return $this->statusLabels[$status];
        }

        return 'inverse';
    }

    /**
     * Returns the HTML to display the status
     *
     * @param  string $status
     * @return string
     */
    public function getHtml($status)
    {
        $status = str_replace(array('-', '_'), ' ', strtolower((string) $status));

        return '<label class="label label-'.$this->getStatusLabel($status).'">'.ucwords($status).'</label>';
    }

    /**
     * Return an array of all the available statuses
     *
     * @return array
     */
    public function getStatusList()
    {
        return array_keys($this->statusLabels);
    }
}
