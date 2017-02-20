<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Controller;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Form\Type\ClientType;
use CSBill\ClientBundle\Model\Status;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * List all the clients.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('CSBillClientBundle:Default:index.html.twig');
    }

    /**
     * Adds a new client.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request)
    {
        $client = new Client();

	$form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isValid()) {
            // set all new clients default to active
            $client->setStatus(Status::STATUS_ACTIVE);

            $this->save($client);

            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', ['id' => $client->getId()]));
        }

        return $this->render('CSBillClientBundle:Default:add.html.twig', ['form' => $form->createView()]);
    }

    /**
     * Edit a client.
     *
     * @param Request $request
     * @param Client  $client
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Client $client)
    {
        $originalContacts = $client->getContacts()->toArray();
        $originalAddresses = $client->getAddresses()->toArray();
        $originalContactsDetails = $this->getClientContactDetails($request, $client);

	$form = $this->createForm(ClientType::class, $client);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $this->removeClientContacts($client, $originalContacts, $originalContactsDetails);
            $this->removeClientAddresses($client, $originalAddresses);

            $this->save($client);
            $this->flash($this->trans('client_saved'), 'success');

            return $this->redirect($this->generateUrl('_clients_view', ['id' => $client->getId()]));
        }

        return $this->render(
            'CSBillClientBundle:Default:edit.html.twig',
            [
                'client' => $client,
                'form' => $form->createView(),
            ]
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
        $originalContactsDetails = [];

        if ($request->isMethod('POST')) {
            $originalContacts = $client->getContacts()->toArray();

            foreach ($originalContacts as $contact) {
                /* @var \CSBill\ClientBundle\Entity\Contact $contact */
                $originalContactsDetails[$contact->getId()] = $contact->getAdditionalDetails()->toArray();
                $contact->getAdditionalDetails()->clear();
            }
        }

        return $originalContactsDetails;
    }

    /**
     * @param Client $client
     * @param array  $originalContacts
     * @param array  $originalContactsDetails
     */
    private function removeClientContacts(Client $client, array $originalContacts, array $originalContactsDetails)
    {
        $entityManager = $this->getEm();

        foreach ($client->getContacts() as $originalContact) {
            foreach ($originalContacts as $key => $toDel) {
                if ($toDel->getId() === $originalContact->getId()) {
                    unset($originalContacts[$key]);
                }
            }
        }

        foreach ($originalContacts as $contact) {
            $client->removeContact($contact);
            $entityManager->remove($contact);
        }

        unset($contact, $key, $toDel);

        foreach ($client->getContacts() as $contact) {
            if (null === $contact->getId()) {
                continue;
            }

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
     * @param Client $client
     * @param array  $originalAddresses
     */
    private function removeClientAddresses(Client $client, array $originalAddresses)
    {
        $entityManager = $this->getEm();

        foreach ($client->getAddresses() as $originalAddress) {
            foreach ($originalAddresses as $key => $toDel) {
                if ($toDel->getId() === $originalAddress->getId()) {
                    unset($originalAddresses[$key]);
                }
            }
        }

        foreach ($originalAddresses as $address) {
            $client->removeAddress($address);
            $entityManager->remove($address);
        }
    }

    /**
     * View a client.
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

        /** @var \CSBill\InvoiceBundle\Repository\InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->getRepository('CSBillInvoiceBundle:Invoice');

        return $this->render(
            'CSBillClientBundle:Default:view.html.twig',
            [
                'client' => $client,
                'payments' => $payments,
                'total_invoices_pending' => $invoiceRepository->getCountByStatus(Graph::STATUS_PENDING, $client),
                'total_invoices_paid' => $invoiceRepository->getCountByStatus(Graph::STATUS_PAID, $client),
                'total_income' => $paymentRepository->getTotalIncome($client),
                'total_outstanding' => $invoiceRepository->getTotalOutstanding($client),
            ]
        );
    }
}
