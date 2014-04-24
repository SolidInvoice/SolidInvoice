<?php

namespace CSBill\PaymentBundle\Controller;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Source\Entity;
use CSBill\CoreBundle\Controller\BaseController;
use CSBill\PaymentBundle\Entity\PaymentMethod;
use CSBill\PaymentBundle\Form\PaymentMethodForm;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends BaseController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $source = new Entity('CSBillPaymentBundle:PaymentMethod');

        // Get a Grid instance
        $grid = $this->get('grid');
        $templating = $this->get('templating');

        $search = $request->get('search');

        $source->manipulateQuery(function (QueryBuilder $queryBuilder) use ($search) {

            if ($search) {
                $aliases = $queryBuilder->getRootAliases();

                $queryBuilder
                    ->andWhere($aliases[0].'.name LIKE :search')
                    ->setParameter('search', "%{$search}%");
            }
        });

        // Attach the source to the grid
        $grid->setSource($source);

        $editIcon = $templating->render('{{ icon("edit") }}');
        $editAction = new RowAction($editIcon, '_payments_edit');
        $editAction->addAttribute('title', $this->trans('edit_payment_method'));
        $editAction->addAttribute('rel', 'tooltip');

        $deleteIcon = $templating->render('{{ icon("times") }}');
        $deleteAction = new RowAction($deleteIcon, '_payments_delete');
        $deleteAction->setAttributes(
            array(
                'title' => $this->trans('delete_payment_method'),
                'rel' => 'tooltip',
                'data-confirm' => $this->trans('confirm_delete'),
                'class' => 'delete-item',
            )
        );

        $actionsRow = new ActionsColumn('actions', 'Action', array($editAction, $deleteAction));
        $grid->addColumn($actionsRow, 100);

        $grid->hideColumns(array('updated', 'deleted', 'settings'));

        return $grid->getGridResponse('CSBillPaymentBundle:Default:index.html.twig', array('filters' => array()));
    }

    /**
     * @param Request       $request
     * @param PaymentMethod $payment
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function addAction(Request $request, PaymentMethod $payment = null)
    {
        $paymentMethod = $payment ?: new PaymentMethod();
        $manager = $this->get('csbill_payment.method.manager');
        $form = $this->createForm(new PaymentMethodForm(), $paymentMethod, array('manager' => $manager));

        $form->handleRequest($request);

        if ($form->isValid()) {
            $entityManager = $this->getEm();
            $entityManager->persist($paymentMethod);
            $entityManager->flush();

            $this->flash($this->trans($payment ? 'payment_method_updated' : 'payment_method_added'), 'success');

            return $this->redirect($this->generateUrl('_payments_index'));
        }

        return $this->render(
            'CSBillPaymentBundle:Default:add.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }
}
