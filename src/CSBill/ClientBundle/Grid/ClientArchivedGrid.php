<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\ClientBundle\Grid;

use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Repository\ClientRepository;
use CSBill\DataGridBundle\AbstractGrid;
use CSBill\DataGridBundle\Action\DeleteMassAction;
use CSBill\DataGridBundle\Action\MassAction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ClientArchivedGrid extends AbstractGrid
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var
     */
    private $session;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface       $session
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        $this->entityManager->getFilters()->disable('archivable');

        $source = new Entity('CSBillClientBundle:Client');

        /** @var ClientRepository $repo */
        $repo = $this->entityManager->getRepository('CSBillClientBundle:Client');

        $queryBuilder = $repo->createQueryBuilder('c');
        $queryBuilder->where('c.archived is not null');

        $source->initQueryBuilder($queryBuilder);

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    public function search(QueryBuilder $queryBuilder, $searchString)
    {
        $aliases = $queryBuilder->getRootAliases();

        $queryBuilder->andWhere($aliases[0].'.name LIKE :search')
            ->setParameter('search', "%{$searchString}%");
    }

    /**
     * {@inheritdoc}
     */
    public function getMassActions()
    {
        $archive = new MassAction('Restore', function ($ids) {
            /** @var ClientRepository $clientRepository */
            $clientRepository = $this->entityManager->getRepository('CSBillClientBundle:Client');

            /** @var Client[] $clients */
            $clients = $clientRepository->findBy(array('id' => $ids));

            foreach ($clients as $client) {
                $client->setArchived(null);
                $this->entityManager->persist($client);
            }

            $this->entityManager->flush();

            /** @var FlashBag $flashBag */
            $flashBag = $this->session->getBag('flashes');
            $flashBag->add('success', 'client.restore.success');
        }, true);

        $archive->setIcon('reply');
        $archive->setClass('success');

        $deleteAction = new DeleteMassAction(true);

        return array(
            $archive,
            $deleteAction,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'CSBillClientBundle:Default:index.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable()
    {
        return true;
    }
}
