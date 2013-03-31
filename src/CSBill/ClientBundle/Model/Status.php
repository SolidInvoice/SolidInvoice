<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Model;

/**
 * This class converts a client status into a label for use with twitter bootstrap
 *
 * E.G it will convert 'active' into 'success', so it can be used with then class 'label-success'
 *
 * @author Pierre du Plessis
 */
class Status {

	/**
	 * Contains a list of all the statuses and their corresponding labels
	 *
	 * @var array
	 */
	protected $statusLabels = array(
									'active' 	=> 'success',
									'inactive' 	=> 'warning'
			);

	/**
	 * Converts a status into a label
	 *
	 * @param string $status
	 * @return string
	 */
	public function getStatusLabel($status)
	{
		if(isset($this->statusLabels[$status]))
		{
			return $this->statusLabels[$status];
		}

		return 'inverse';
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