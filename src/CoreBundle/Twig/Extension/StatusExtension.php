<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Exception;
use SolidInvoice\ClientBundle\Model\Status as ClientStatus;
use SolidInvoice\InvoiceBundle\Model\Graph as InvoiceGraph;
use SolidInvoice\PaymentBundle\Model\Status as PaymentStatus;
use SolidInvoice\QuoteBundle\Model\Graph as QuoteGraph;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StatusExtension extends AbstractExtension
{
    /**
     * @var array<string, string>
     */
    private array $invoiceLabelMap = [
        InvoiceGraph::STATUS_PENDING => 'yellow',
        InvoiceGraph::STATUS_DRAFT => 'secondary',
        InvoiceGraph::STATUS_PAID => 'green',
        InvoiceGraph::STATUS_ACTIVE => 'green',
        InvoiceGraph::STATUS_OVERDUE => 'red',
        InvoiceGraph::STATUS_CANCELLED => 'olive',
        InvoiceGraph::STATUS_ARCHIVED => 'purple',
    ];

    /**
     * @var array<string, string>
     */
    private array $quoteLabelMap = [
        QuoteGraph::STATUS_PENDING => 'yellow',
        QuoteGraph::STATUS_DRAFT => 'secondary',
        QuoteGraph::STATUS_ACCEPTED => 'green',
        QuoteGraph::STATUS_DECLINED => 'red',
        QuoteGraph::STATUS_CANCELLED => 'olive',
        QuoteGraph::STATUS_ARCHIVED => 'purple',
    ];

    /**
     * @var array<string, string>
     */
    private array $paymentLabelMap = [
        PaymentStatus::STATUS_UNKNOWN => 'primary',
        PaymentStatus::STATUS_FAILED => 'red',
        PaymentStatus::STATUS_SUSPENDED => 'black',
        PaymentStatus::STATUS_EXPIRED => 'purple',
        PaymentStatus::STATUS_CAPTURED => 'green',
        PaymentStatus::STATUS_PENDING => 'yellow',
        PaymentStatus::STATUS_CANCELLED => 'navy',
        PaymentStatus::STATUS_NEW => 'blue',
        PaymentStatus::STATUS_AUTHORIZED => 'aqua',
        PaymentStatus::STATUS_REFUNDED => 'maroon',
        PaymentStatus::STATUS_CREDIT => 'fuchsia',
    ];

    /**
     * @var array<string, string>
     */
    private array $clientLabelMap = [
        ClientStatus::STATUS_ACTIVE => 'green',
        ClientStatus::STATUS_INACTIVE => 'aqua',
        ClientStatus::STATUS_ARCHIVED => 'purple',
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'invoice_label',
                fn (Environment $environment, string $status = null, $tooltip = null) => $this->renderInvoiceStatusLabel($environment, $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'quote_label',
                fn (Environment $environment, string $status = null, $tooltip = null) => $this->renderQuoteStatusLabel($environment, $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'payment_label',
                fn (Environment $environment, string $status = null, $tooltip = null) => $this->renderPaymentStatusLabel($environment, $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'client_label',
                fn (Environment $environment, string $status = null, $tooltip = null) => $this->renderClientStatusLabel($environment, $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * @return string|array<string, string>
     *
     * @throws Exception
     */
    public function renderInvoiceStatusLabel(Environment $environment, string $status = null, string $tooltip = null): string|array
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->invoiceLabelMap);
        }

        if (! isset($this->invoiceLabelMap[$status])) {
            throw new Exception(sprintf('The invoice status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->invoiceLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @param array<string, string> $labelMap
     *
     * @return array<string, string>
     */
    private function getAllStatusLabels(Environment $environment, array $labelMap): array
    {
        $response = [];

        foreach ($labelMap as $status => $label) {
            $response[$status] = $this->renderStatusLabel($environment, ['status' => $status, 'status_label' => $label]);
        }

        return $response;
    }

    /**
     * Return the status converted into a label string.
     */
    private function renderStatusLabel(Environment $environment, mixed $object, string $tooltip = null): string
    {
        if (is_array($object) && array_key_exists('status_label', $object) && array_key_exists('status', $object)) {
            $object = [
                'name' => $object['status'],
                'label' => $object['status_label'],
            ];
        }

        return $environment->render(
            '@SolidInvoiceCore/Status/label.html.twig',
            [
                'entity' => $object,
                'tooltip' => $tooltip,
            ]
        );
    }

    /**
     * @return string|array<string, string>
     *
     * @throws Exception
     */
    public function renderQuoteStatusLabel(Environment $environment, string $status = null, string $tooltip = null): string|array
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->quoteLabelMap);
        }

        if (! isset($this->quoteLabelMap[$status])) {
            throw new Exception(sprintf('The quote status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->quoteLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @return string|array<string, string>
     *
     * @throws Exception
     */
    public function renderPaymentStatusLabel(Environment $environment, string $status = null, string $tooltip = null): string|array
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->paymentLabelMap);
        }

        if (! isset($this->paymentLabelMap[$status])) {
            throw new Exception(sprintf('The payment status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->paymentLabelMap[$status],
        ];

        return $this->renderStatusLabel($environment, $statusLabel, $tooltip);
    }

    /**
     * @return string|array<string, string>
     *
     * @throws Exception
     */
    public function renderClientStatusLabel(Environment $environment, string $status = null, string $tooltip = null): string|array
    {
        if (null === $status) {
            return $this->getAllStatusLabels($environment, $this->clientLabelMap);
        }

        if (! isset($this->clientLabelMap[$status])) {
            throw new Exception(sprintf('The client status "%s" does not have an associative label', $status));
        }

        $statusLabel = [
            'status' => $status,
            'status_label' => $this->clientLabelMap[$status],
        ];

        return trim($this->renderStatusLabel($environment, $statusLabel, $tooltip));
    }
}
