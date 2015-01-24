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

use CSBill\InvoiceBundle\Model\Graph;

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
        Graph::STATUS_PENDING => 'warning',
        Graph::STATUS_DRAFT => 'default',
        Graph::STATUS_OVERDUE => 'danger',
        Graph::STATUS_PAID => 'success',
        Graph::STATUS_CANCELLED => 'inverse',
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
                'status_label',
                array($this, 'renderStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
            new \Twig_SimpleFunction(
                'invoice_label',
                array($this, 'renderInvoiceStatusLabel'),
                array('is_safe' => array('html'),
                )
            ),
        );
    }

    /**
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter(
                'status',
                array($this, 'renderStatusLabel'),
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
            'status_label' => $this->invoiceLabelMap[$status]
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
    public function renderStatusLabel($object, $tooltip = null)
    {
        if (is_array($object) && array_key_exists('status_label', $object) && array_key_exists('status', $object)) {
            $object = array(
                'name' => $object['status'],
                'label' => $object['status_label'],
            );
        }

        try {
            return $this->environment->render(
                'CSBillCoreBundle:Status:label.html.twig',
                array(
                    'entity' => $object,
                    'tooltip' => $tooltip,
                )
            );
        } catch (\Twig_Error_Runtime $e) {
            var_dump($object, $e);
            exit;
        }

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
