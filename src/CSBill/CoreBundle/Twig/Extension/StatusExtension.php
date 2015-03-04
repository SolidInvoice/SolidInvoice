<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Twig\Extension;

use CSBill\ClientBundle\Model\Status as ClientStatus;
use CSBill\InvoiceBundle\Model\Graph as InvoiceGraph;
use CSBill\PaymentBundle\Model\Status as PaymentStatus;
use CSBill\QuoteBundle\Model\Graph as QuoteGraph;

/**
 * This class is a twig extension that gives some shortcut methods to client statuses
 *
 * @author Pierre du Plessis
 */
class StatusExtension extends \Twig_Extension
{
    /**
     * @var array
     */
    private $invoiceLabelMap = array(
        InvoiceGraph::STATUS_PENDING => 'warning',
        InvoiceGraph::STATUS_DRAFT => 'primary',
        InvoiceGraph::STATUS_PAID => 'success',
        InvoiceGraph::STATUS_OVERDUE => 'danger',
        InvoiceGraph::STATUS_CANCELLED => 'default',
        InvoiceGraph::STATUS_ARCHIVED => 'warning',
    );

    /**
     * @var array
     */
    private $quoteLabelMap = array(
        QuoteGraph::STATUS_PENDING => 'warning',
        QuoteGraph::STATUS_DRAFT => 'primary',
        QuoteGraph::STATUS_ACCEPTED => 'success',
        QuoteGraph::STATUS_DECLINED => 'danger',
        QuoteGraph::STATUS_CANCELLED => 'default',
        QuoteGraph::STATUS_ARCHIVED => 'warning',
    );

    /**
     * @var array
     */
    private $paymentLabelMap = array(
        PaymentStatus::STATUS_UNKNOWN => 'default',
        PaymentStatus::STATUS_FAILED => 'danger',
        PaymentStatus::STATUS_SUSPENDED => 'warning',
        PaymentStatus::STATUS_EXPIRED => 'danger',
        PaymentStatus::STATUS_CAPTURED => 'success',
        PaymentStatus::STATUS_PENDING => 'warning',
        PaymentStatus::STATUS_CANCELLED => 'default',
        PaymentStatus::STATUS_NEW => 'info',
        PaymentStatus::STATUS_AUTHORIZED => 'primary',
        PaymentStatus::STATUS_REFUNDED => 'warning',
        PaymentStatus::STATUS_CREDIT => 'success',
    );

    /**
     * @var array
     */
    private $clientLabelMap = array(
        ClientStatus::STATUS_ACTIVE => 'success',
        ClientStatus::STATUS_INACTIVE => 'info',
        ClientStatus::STATUS_ARCHIVED => 'warning',
    );

    /**
     * @var \Twig_Environment
     */
    protected $environment;

    /**
     * @param \Twig_Environment $environment
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Returns an array of all the helper functions for the client status
     *
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(
                'invoice_label',
                array($this, 'renderInvoiceStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
            new \Twig_SimpleFunction(
                'quote_label',
                array($this, 'renderQuoteStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
            new \Twig_SimpleFunction(
                'payment_label',
                array($this, 'renderPaymentStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
            new \Twig_SimpleFunction(
                'client_label',
                array($this, 'renderClientStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
        );
    }

    /**
     * @param string $status
     * @param string $tooltip
     *
     * @return string
     * @throws \Exception
     */
    public function renderInvoiceStatusLabel($status, $tooltip = null)
    {
        if (!isset($this->invoiceLabelMap[$status])) {
            throw new \Exception(sprintf('The invoice status "%s" does not have an associative label', $status));
        }

        $statusLabel = array(
            'status' => $status,
            'status_label' => $this->invoiceLabelMap[$status],
        );

        return $this->renderStatusLabel($statusLabel, $tooltip);
    }

    /**
     * @param string $status
     * @param string $tooltip
     *
     * @return string
     * @throws \Exception
     */
    public function renderQuoteStatusLabel($status, $tooltip = null)
    {
        if (!isset($this->quoteLabelMap[$status])) {
            throw new \Exception(sprintf('The quote status "%s" does not have an associative label', $status));
        }

        $statusLabel = array(
            'status' => $status,
            'status_label' => $this->quoteLabelMap[$status],
        );

        return $this->renderStatusLabel($statusLabel, $tooltip);
    }

    /**
     * @param string $status
     * @param string $tooltip
     *
     * @return string
     * @throws \Exception
     */
    public function renderPaymentStatusLabel($status, $tooltip = null)
    {
        if (!isset($this->paymentLabelMap[$status])) {
            throw new \Exception(sprintf('The payment status "%s" does not have an associative label', $status));
        }

        $statusLabel = array(
            'status' => $status,
            'status_label' => $this->paymentLabelMap[$status],
        );

        return $this->renderStatusLabel($statusLabel, $tooltip);
    }

    /**
     * @param string $status
     * @param string $tooltip
     *
     * @return string
     * @throws \Exception
     */
    public function renderClientStatusLabel($status, $tooltip = null)
    {
        if (!isset($this->clientLabelMap[$status])) {
            throw new \Exception(sprintf('The client status "%s" does not have an associative label', $status));
        }

        $statusLabel = array(
            'status' => $status,
            'status_label' => $this->clientLabelMap[$status],
        );

        return $this->renderStatusLabel($statusLabel, $tooltip);
    }

    /**
     * Return the status converted into a label string
     *
     * @param mixed  $object
     * @param string $tooltip
     *
     * @return string
     */
    private function renderStatusLabel($object, $tooltip = null)
    {
        if (is_array($object) && array_key_exists('status_label', $object) && array_key_exists('status', $object)) {
            $object = array(
                'name' => $object['status'],
                'label' => $object['status_label'],
            );
        }

        return $this->environment->render(
            'CSBillCoreBundle:Status:label.html.twig',
            array(
                'entity' => $object,
                'tooltip' => $tooltip,
            )
        );
    }

    /**
     * Get the name of the twig extension
     *
     * @return string
     */
    public function getName()
    {
        return 'csbill_core.status';
    }
}
