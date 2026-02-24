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

use BackedEnum;
use Exception;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StatusExtension extends AbstractExtension
{
    /**
     * @var array<string, string>
     */
    private array $invoiceLabelMap = [
        InvoiceStatus::New->value => 'grey',
        InvoiceStatus::Pending->value => 'yellow',
        InvoiceStatus::Draft->value => 'secondary',
        InvoiceStatus::Paid->value => 'green',
        InvoiceStatus::Active->value => 'green',
        InvoiceStatus::Overdue->value => 'red',
        InvoiceStatus::Cancelled->value => 'olive',
        InvoiceStatus::Archived->value => 'purple',
        RecurringInvoiceStatus::Paused->value => 'black',
    ];

    /**
     * @var array<string, string>
     */
    private array $quoteLabelMap = [
        QuoteStatus::Pending->value => 'yellow',
        QuoteStatus::Draft->value => 'secondary',
        QuoteStatus::Accepted->value => 'green',
        QuoteStatus::Declined->value => 'red',
        QuoteStatus::Cancelled->value => 'olive',
        QuoteStatus::Archived->value => 'purple',
    ];

    /**
     * @var array<string, string>
     */
    private array $paymentLabelMap = [
        PaymentStatus::Unknown->value => 'primary',
        PaymentStatus::Failed->value => 'red',
        PaymentStatus::Suspended->value => 'black',
        PaymentStatus::Expired->value => 'purple',
        PaymentStatus::Captured->value => 'green',
        PaymentStatus::Pending->value => 'yellow',
        PaymentStatus::Cancelled->value => 'navy',
        PaymentStatus::New->value => 'blue',
        PaymentStatus::Authorized->value => 'aqua',
        PaymentStatus::Refunded->value => 'maroon',
        PaymentStatus::Credit->value => 'fuchsia',
    ];

    /**
     * @var array<string, string>
     */
    private array $clientLabelMap = [
        ClientStatus::Active->value => 'green',
        ClientStatus::Inactive->value => 'aqua',
        ClientStatus::Archived->value => 'purple',
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'invoice_label',
                fn (Environment $environment, string|BackedEnum|null $status = null, ?string $tooltip = null) => $this->renderInvoiceStatusLabel($environment, $status instanceof BackedEnum ? $status->value : $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'quote_label',
                fn (Environment $environment, string|BackedEnum|null $status = null, ?string $tooltip = null) => $this->renderQuoteStatusLabel($environment, $status instanceof BackedEnum ? $status->value : $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'payment_label',
                fn (Environment $environment, string|BackedEnum|null $status = null, ?string $tooltip = null) => $this->renderPaymentStatusLabel($environment, $status instanceof BackedEnum ? $status->value : $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'client_label',
                fn (Environment $environment, string|BackedEnum|null $status = null, ?string $tooltip = null) => $this->renderClientStatusLabel($environment, $status instanceof BackedEnum ? $status->value : $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * @return string|array<string, string>
     *
     * @throws Exception
     */
    public function renderInvoiceStatusLabel(Environment $environment, ?string $status = null, ?string $tooltip = null): string|array
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
    private function renderStatusLabel(Environment $environment, mixed $object, ?string $tooltip = null): string
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
    public function renderQuoteStatusLabel(Environment $environment, ?string $status = null, ?string $tooltip = null): string|array
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
    public function renderPaymentStatusLabel(Environment $environment, ?string $status = null, ?string $tooltip = null): string|array
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
    public function renderClientStatusLabel(Environment $environment, ?string $status = null, ?string $tooltip = null): string|array
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
