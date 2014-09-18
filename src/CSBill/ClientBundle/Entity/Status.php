<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Entity;

use CSBill\CoreBundle\Entity\Status as BaseStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

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
        $this->clients = new ArrayCollection();
    }

    /**
     * @param Client $client
     *
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
     *
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
