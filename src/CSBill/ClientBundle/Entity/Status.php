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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use CSBill\CoreBundle\Entity\Status as BaseStatus;

/**
 * CSBill\ClientBundle\Entity\Status
 *
 * @ORM\Entity
 */
class Status extends BaseStatus
{
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Client", mappedBy="status")
     */
    protected $clients;

    public function __construct()
    {
        parent::__construct();

        $this->clients = new ArrayCollection();
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;
        $client->setStatus($this);

        return $this;
    }

    /**
     * @param Client $client
     * @return $this
     */
    public function removeClient(Client $client)
    {
        $this->clients->removeElement($client);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getClients()
    {
        return $this->clients;
    }
}
