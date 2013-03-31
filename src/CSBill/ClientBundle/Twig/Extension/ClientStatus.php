<?php

/*
 * This file is part of the CSBill package.
*
* (c) Pierre du Plessis <info@customscripts.co.za>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CSBill\ClientBundle\Twig\Extension;

use Twig_Extension;
use Twig_Function_Method;
use CSBill\ClientBundle\Entity\Client;

/**
 * This class is a twig extension that gives some shortcut methods to client statuses
 *
 * @author Pierre du Plessis
 */
class ClientStatus extends Twig_Extension
{
	/**
	 * Contains the class used to convert a status into a label
	 *
	 * @var object
	 */
	protected $statusClass;

	/**
	 * Sets the status class instance
	 *
	 * @param object $class
	 */
	public function setStatusClass($class)
	{
		$this->statusClass = $class;
	}

	/**
	 * Returns an array of all the helper functions for the client status
	 *
	 * @return array
	 */
	public function getFunctions()
	{
		return array('client_status_label'=> new Twig_Function_Method($this, 'getClientStatusLabel'));
	}

	/**
	 * Return the client status converted into a label string
	 *
	 * @param Client $client
	 * @return string
	 */
	public function getClientStatusLabel(Client $client)
	{
		return $this->statusClass->getStatusLabel(strtolower((string) $client->getStatus()));
	}

	/**
	 * Get the name of the twig extension
	 *
	 * @return string
	 */
	public function getName()
	{
		return 'csbill_client.status';
	}
}