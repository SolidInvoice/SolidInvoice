<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Entity;

use CS\ClientBundle\Entity\Client as BaseClient;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collection\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="CS\ClientBundle\Repository\ClientRepository")
 */
class Client extends BaseClient {

	/**
	 * @var ArrayCollection $quotes
	 *
	 * @ORM\OneToMany(targetEntity="CSBill\QuoteBundle\Entity\Quote", mappedBy="client")
	 * @Assert\Valid()
	 */
	private $quotes;

	/**
	 * Constructer
	 */
	public function __construct()
	{
		$this->quotes = new ArrayCollection;
	}

	/**
	 * Add quote
	 *
	 * @param  Quote  $quote
	 * @return Client
	 */
	public function addQuote(Quote $quote)
	{
		$this->quotes[] = $quote;
		$quote->setClient($this);

		return $this;
	}

	/**
	 * Remove quote
	 *
	 * @param  Quote  $quote
	 * @return Client
	 */
	public function removeQuote(Quote $quote)
	{
		$this->quotes->removeElement($quote);

		return $this;
	}

	/**
	 * Get quotes
	 *
	 * @return ArrayCollection
	 */
	public function getQuotes()
	{
		return $this->quotes;
	}
}