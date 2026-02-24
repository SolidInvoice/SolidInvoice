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

use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StatusExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'invoice_label',
                fn (Environment $environment, InvoiceStatus|RecurringInvoiceStatus|null $status = null, ?string $tooltip = null) => $this->renderInvoiceStatusLabel($environment, $status, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'quote_label',
                fn (Environment $environment, QuoteStatus|null $status = null, ?string $tooltip = null) => $this->renderStatusOrAll($environment, $status, QuoteStatus::class, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'payment_label',
                fn (Environment $environment, PaymentStatus|null $status = null, ?string $tooltip = null) => $this->renderStatusOrAll($environment, $status, PaymentStatus::class, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
            new TwigFunction(
                'client_label',
                fn (Environment $environment, ClientStatus|null $status = null, ?string $tooltip = null) => $this->renderStatusOrAll($environment, $status, ClientStatus::class, $tooltip),
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * @template T of (HasStatusLabel&\BackedEnum)
     * @param class-string<T> $enumClass
     *
     * @return string|array<string, string>
     */
    private function renderStatusOrAll(Environment $environment, ?HasStatusLabel $status, string $enumClass, ?string $tooltip = null): string|array
    {
        if ($status === null) {
            return $this->getAllStatusLabels($environment, $enumClass);
        }

        return $this->renderStatusLabel($environment, $status, $tooltip);
    }

    /**
     * @return string|array<string, string>
     */
    public function renderInvoiceStatusLabel(Environment $environment, InvoiceStatus|RecurringInvoiceStatus|null $status = null, ?string $tooltip = null): string|array
    {
        if ($status === null) {
            return array_merge(
                $this->getAllStatusLabels($environment, InvoiceStatus::class),
                $this->getAllStatusLabels($environment, RecurringInvoiceStatus::class)
            );
        }

        return $this->renderStatusLabel($environment, $status, $tooltip);
    }

    /**
     * @template T of (HasStatusLabel&\BackedEnum)
     * @param class-string<T> $enumClass
     *
     * @return array<string, string>
     */
    private function getAllStatusLabels(Environment $environment, string $enumClass): array
    {
        $response = [];

        foreach ($enumClass::cases() as $case) {
            $response[$case->value] = $this->renderStatusLabel($environment, $case);
        }

        return $response;
    }

    private function renderStatusLabel(Environment $environment, HasStatusLabel $status, ?string $tooltip = null): string
    {
        return $environment->render(
            '@SolidInvoiceCore/Status/label.html.twig',
            [
                'entity' => [
                    'name' => $status->getLabel(),
                    'label' => $status->getColor(),
                ],
                'tooltip' => $tooltip,
            ]
        );
    }
}
