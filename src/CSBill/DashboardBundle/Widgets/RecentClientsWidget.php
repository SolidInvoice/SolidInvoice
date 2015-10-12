<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DashboardBundle\Widgets;

use CSBill\ClientBundle\Repository\ClientRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class RecentClientsWidget implements WidgetInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $manager;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array
     */
    public function getData()
    {
        /** @var ClientRepository $clientRepository */
        $clientRepository = $this->manager->getRepository('CSBillClientBundle:Client');

        $clients = $clientRepository->getRecentClients();

        return array('clients' => $clients);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return 'CSBillDashboardBundle:Widget:recent_clients.html.twig';
    }
}
