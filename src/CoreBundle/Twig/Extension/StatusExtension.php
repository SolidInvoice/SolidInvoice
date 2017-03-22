<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
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
 * This class is a twig extension that gives some shortcut methods to client statuses.
 *
 * @author Pierre du Plessis
 */
class StatusExtension extends \Twig_Extension
{
    /**
     * @var array
     */
    private $invoiceLabelMap = [
        InvoiceGraph::STATUS_PENDING => 'warning',
        InvoiceGraph::STATUS_DRAFT => 'primary',
        InvoiceGraph::STATUS_PAID => 'success',
        InvoiceGraph::STATUS_OVERDUE => 'danger',
        InvoiceGraph::STATUS_CANCELLED => 'default',
        InvoiceGraph::STATUS_ARCHIVED => 'warning',
        InvoiceGraph::STATUS_RECURRING => 'warning',
    ];

    /**
     * @var array
     */
    private $quoteLabelMap = [
        QuoteGraph::STATUS_PENDING => 'warning',
        QuoteGraph::STATUS_DRAFT => 'primary',
        QuoteGraph::STATUS_ACCEPTED => 'success',
        QuoteGraph::STATUS_DECLINED => 'danger',
        QuoteGraph::STATUS_CANCELLED => 'default',
        QuoteGraph::STATUS_ARCHIVED => 'warning',
    ];

    /**
     * @var array
     */
    private $paymentLabelMap = [
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
    ];

    /**
     * @var array
     */
    private $clientLabelMap = [
        ClientStatus::STATUS_ACTIVE => 'success',
        ClientStatus::STATUS_INACTIVE => 'info',
        ClientStatus::STATUS_ARCHIVED => 'warning',
    ];

    /**
     * Returns an array of all the helper functions for the client status.
     *
     * @return \Twig_SimpleFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction(
                'invoice_label',
                [$this, 'renderInvoiceStatusLabel'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'quote_label',
                [$this, 'renderQuoteStatusLabel'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'payment_label',
                [$this, 'renderPaymentStatusLabel'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new \Twig_SimpleFunction(
                'client_label',
                [$this, 'renderClientStatusLabel'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $status
     * @param string            $tooltip
     *
     * @return string|array
     *
     * @throws \Exception
     */
    public function renderInvoiceStatusLabel(\Twig_Environment $environment, string $status = null, $tooltip = null)
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->invoiceLabelMap);
        }

        if (!isset($this->invoiceLabelMap[$status])) {
            throw new \Exception(sprintf('The invoice status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->invoiceLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @param \Twig_Environment $environment
     * @param array             $labelMap
     *
     * @return array
     */
    private function getAllStatusLabels(\Twig_Environment $environment, array $labelMap): array
    {
        $response = [];

        foreach ($labelMap as $status => $label) {
            $response[$status] = $this->renderStatusLabel($environment, ['status' => $status, 'status_label' => $label]);
        }

        return $response;
    }

    /**
     * Return the status converted into a label string.
     *
     * @param \Twig_Environment $environment
     * @param mixed             $object
     * @param string            $tooltip
     *
     * @return string
     */
    private function renderStatusLabel(\Twig_Environment $environment, $object, string $tooltip = null): string
    {
        if (is_array($object) && array_key_exists('status_label', $object) && array_key_exists('status', $object)) {
            $object = [
                'name' => $object['status'],
                'label' => $object['status_label'],
            ];
        }

        return $environment->render(
            'CSBillCoreBundle:Status:label.html.twig',
            [
                'entity' => $object,
                'tooltip' => $tooltip,
            ]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $status
     * @param string            $tooltip
     *
     * @return string|array
     *
     * @throws \Exception
     */
    public function renderQuoteStatusLabel(\Twig_Environment $environment, string $status = null, $tooltip = null)
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->quoteLabelMap);
        }

        if (!isset($this->quoteLabelMap[$status])) {
            throw new \Exception(sprintf('The quote status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->quoteLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $status
     * @param string            $tooltip
     *
     * @return string|array
     *
     * @throws \Exception
     */
    public function renderPaymentStatusLabel(\Twig_Environment $environment, string $status = null, $tooltip = null)
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->paymentLabelMap);
        }

        if (!isset($this->paymentLabelMap[$status])) {
            throw new \Exception(sprintf('The payment status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->paymentLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @param \Twig_Environment $environment
     * @param string            $status
     * @param string            $tooltip
     *
     * @return string|array
     *
     * @throws \Exception
     */
    public function renderClientStatusLabel(\Twig_Environment $environment, string $status = null, $tooltip = null)
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->clientLabelMap);
        }

        if (!isset($this->clientLabelMap[$status])) {
            throw new \Exception(sprintf('The client status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->clientLabelMap[$status],
        ];

        return trim($this->renderStatusLabel($environment, $statusLabel, $tooltip));
    }

    /**
     * Get the name of the twig extension.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'csbill_core.status';
    }
}
