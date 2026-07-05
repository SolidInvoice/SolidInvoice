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
use Override;
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class StatusExtension extends AbstractExtension
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
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
     * @template T of HasStatusLabel&BackedEnum
     * @param class-string<T> $enumClass
     *
     * @return string|array<string, string>
     */
    private function renderStatusOrAll(Environment $environment, ?HasStatusLabel $status, string $enumClass, ?string $tooltip = null): string|array
    {
        if (! $status instanceof HasStatusLabel) {
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
     * @template T of HasStatusLabel&BackedEnum
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
        // Translate the status at this single display chokepoint via a shared `status.*`
        // key (keyed by the enum's backing value), so the catalog stays the source of
        // truth. getLabel() itself is left untranslated for its non-display consumers
        // (grids, filters, API), and the English catalog value equals getLabel() so
        // rendered output is unchanged.
        $key = $status instanceof BackedEnum ? $status->value : strtolower($status->getLabel());

        return $environment->render(
            '@SolidInvoiceCore/Status/label.html.twig',
            [
                'entity' => [
                    'name' => $this->translator->trans('status.' . $key),
                    'label' => $status->getColor(),
                ],
                'tooltip' => $tooltip,
            ]
        );
    }
}
