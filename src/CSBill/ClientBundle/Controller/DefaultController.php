<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Entity\Status;
use CSBill\ClientBundle\Form\Client as ClientForm;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\DataGridBundle\Grid\Filters;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * List all the clients
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        return $this->getGrid($request);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getGrid(Request $request)
    {
        $source = new Entity('CSBillClientBundle:Client');

        // Get a Grid instance
        $grid = $this->get('grid');
        $translator = $this->get('translator');
        $templating = $this->get('templating');

        $filters = $this->getFilters($request);

        $search = $request->get('search');

        $source->manipulateQuery(
            function (QueryBuilder $queryBuilder) use ($search, $filters) {

                if ($filters->isFilterActive()) {
                    $filter = $filters->getActiveFilter();
                    $filter($queryBuilder);
                }

                if ($search) {
                    $aliases = $queryBuilder->getRootAliases();

                    $queryBuilder->andWhere($aliases[0] . '.name LIKE :search')
                        ->setParameter('search', "%{$search}%");
                }
            }
        );

        // Attach the source to the grid
        $grid->setSource($source);

        $viewIcon = $templating->render('{{ icon("eye") }}');
        $viewAction = new RowAction($viewIcon, '_clients_view');
        $viewAction->addAttribute('title', $translator->trans('view_client'));
        $viewAction->addAttribute('rel', 'tooltip');

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_clients_edit');
        $editAction->addAttribute('title', $translator->trans('edit_client'));
        $editAction->addAttribute('rel', 'tooltip');

        $deleteIcon = $templating->render('{{ icon("times") }}');
        $deleteAction = new RowAction($deleteIcon, '_clients_delete');
        $deleteAction->setAttributes(
            array(
                'title' => $translator->trans('delete_client'),
                'rel' => 'tooltip',
                'data-confirm' => $translator->trans('confirm_delete'),
                'class' => 'delete-client',
            )
        );

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $viewAction, $deleteAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deletedAt'));

        $grid->getColumn('website')->manipulateRenderCell(
            function ($value) {
                if (!empty($value)) {
                    return '<a href="' . $value . '" target="_blank">' . $value . '<a>';
                }

                return $value;
            }
        )->setSafe(false);

        $grid->getColumn('status.name')->manipulateRenderCell(
            function ($value, \APY\DataGridBundle\Grid\Row $row) {
                $label = $row->getField('status.label');

                return '<span class="label label-' . $label . '">' . ucfirst($value) . '</span>';
            }
        )->setSafe(false);

        return $grid->getGridResponse('CSBillClientBundle:Default:index.html.twig', array('filters' => $filters));
    }

    /**
     * @param Request $request
     *
     * @return Filters
     */
    private function getFilters(Request $request)
    {
        $filters = new Filters($request);

        $filters->add(
            'all_clients',
            null,
            true,
            array(
                'active_class' => 'label label-info',
                'default_class' => 'label label-default',
            )
        );

        $statusList = $this->getRepository('CSBillClientBundle:Status')->findAll();

        foreach ($statusList as $status) {
            $filters->add(
                $status->getName() . '_clients',
                function (QueryBuilder $queryBuilder) use ($status) {
                    $aliases = $queryBuilder->getRootAliases();
                    $alias = $aliases[0];

                    $queryBuilder->join($alias . '.status', 's')
                        ->andWhere('s.name = :status_name')
                        ->setParameter('status_name', $status->getName());
                },
                false,
                array(
                    'active_class' => 'label label-' . $status->getLabel(),
                    'default_class' => 'label label-default',
                )
            );
        }

        return $filters;
    }

    /**
     * Adds a new client
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $client = new Client();

        $form = $this->createForm(new ClientForm(), $client);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // set all new clients default to active
            $client->setStatus(
                $this->getRepository('CSBillClientBundle:Status')
                    ->findOneBy(array('name' => Status::STATUS_ACTIVE))
            );

            $this->save($client);

            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', array('id' => $client->getId())));
        }

        return $this->render('CSBillClientBundle:Default:add.html.twig', array('form' => $form->createView()));
    }

    /**
     * Edit a client
     *
     * @param Request $request
     * @param Client  $client
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Client $client)
    {
        $form = $this->createForm(new ClientForm(), $client);

        $originalContactsDetails = $this->getClientContactDetails($request, $client);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->removeClientContacts($client, $originalContactsDetails);

            $this->save($client);
            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', array('id' => $client->getId())));
        }

        return $this->render(
            'CSBillClientBundle:Default:edit.html.twig',
            array(
                'client' => $client,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param Request $request
     * @param Client  $client
     *
     * @return array
     */
    private function getClientContactDetails(Request $request, Client $client)
    {
        $originalContactsDetails = array();

        if ($request->isMethod('POST')) {
            $originalContacts = $client->getContacts()->toArray();

            foreach ($originalContacts as $contact) {
                /** @var \CSBill\ClientBundle\Entity\Contact $contact */
                $originalContactsDetails[$contact->getId()] = $contact->getAdditionalDetails()->toArray();
                $contact->getAdditionalDetails()->clear();
            }
        }

        return $originalContactsDetails;
    }

    /**
     * @param Client $client
     * @param array  $originalContactsDetails
     */
    private function removeClientContacts(Client $client, array $originalContactsDetails)
    {
        $entityManager = $this->getEm();

        $originalContacts = $client->getContacts()->toArray();

        foreach ($client->getContacts() as $originalContact) {
            foreach ($originalContacts as $key => $toDel) {
                if ($toDel->getId() === $originalContact->getId()) {
                    unset($originalContacts[$key]);
                }
            }
        }

        foreach ($originalContacts as $contact) {
            $entityManager->remove($contact);
            $client->removeContact($contact);
        }

        unset($contact, $key, $toDel);

        foreach ($client->getContacts() as $contact) {
            foreach ($contact->getAdditionalDetails() as $originalContactDetail) {
                foreach ($originalContactsDetails[$contact->getId()] as $key => $toDel) {
                    if ($toDel->getId() === $originalContactDetail->getId()) {
                        unset($originalContactsDetails[$contact->getId()][$key]);
                    }
                }
            }

            foreach ($originalContactsDetails[$contact->getId()] as $contactDetail) {
                $entityManager->remove($contactDetail);
                $contact->removeAdditionalDetail($contactDetail);
            }
        }
    }

    /**
     * View a client
     *
     * @param Client $client
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewAction(Client $client)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->getRepository('CSBillPaymentBundle:Payment');
        $payments = $paymentRepository->getPaymentsForClient($client);

        return $this->render(
            'CSBillClientBundle:Default:view.html.twig',
            array(
                'client' => $client,
                'payments' => $payments,
            )
        );
    }
}
