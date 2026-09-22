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
use SolidInvoice\ClientBundle\Enum\ClientStatus;
use SolidInvoice\CoreBundle\Enum\HasStatusLabel;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Attribute\AsTwigFunction;
use Twig\Environment;

/**
 * Each Twig function has its own method, so that the enum class stays fixed in PHP.
 * Do not stack more than one AsTwigFunction attribute on a shared method. Twig then
 * makes the enum class an argument of the template call, and every template that
 * calls the function fails to compile.
 *
 * Each function still accepts a `$tooltip` argument. The chip no longer renders a
 * tooltip, so the value is ignored. The parameter stays so that an existing
 * `invoice_label(status, 'text')` call in a template does not become a Twig error.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\StatusExtensionTest
 */
final readonly class StatusExtension
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @return string|array<string, string>
     */
    #[AsTwigFunction(name: 'invoice_label', needsEnvironment: true, isSafe: ['html'])]
    public function renderInvoiceStatusLabel(Environment $environment, InvoiceStatus | RecurringInvoiceStatus | null $status = null, ?string $tooltip = null): string | array
    {
        if ($status === null) {
            return array_merge(
                $this->getAllStatusLabels($environment, InvoiceStatus::class),
                $this->getAllStatusLabels($environment, RecurringInvoiceStatus::class)
            );
        }

        return $this->renderStatusLabel($environment, $status);
    }

    /**
     * @return string|array<string, string>
     */
    #[AsTwigFunction(name: 'quote_label', needsEnvironment: true, isSafe: ['html'])]
    public function renderQuoteStatusLabel(Environment $environment, ?QuoteStatus $status = null, ?string $tooltip = null): string | array
    {
        return $this->renderStatusOrAll($environment, $status, QuoteStatus::class);
    }

    /**
     * @return string|array<string, string>
     */
    #[AsTwigFunction(name: 'payment_label', needsEnvironment: true, isSafe: ['html'])]
    public function renderPaymentStatusLabel(Environment $environment, ?PaymentStatus $status = null, ?string $tooltip = null): string | array
    {
        return $this->renderStatusOrAll($environment, $status, PaymentStatus::class);
    }

    /**
     * @return string|array<string, string>
     */
    #[AsTwigFunction(name: 'client_label', needsEnvironment: true, isSafe: ['html'])]
    public function renderClientStatusLabel(Environment $environment, ?ClientStatus $status = null, ?string $tooltip = null): string | array
    {
        return $this->renderStatusOrAll($environment, $status, ClientStatus::class);
    }

    /**
     * @template T of HasStatusLabel&BackedEnum
     * @param class-string<T> $enumClass
     *
     * @return string|array<string, string>
     */
    private function renderStatusOrAll(Environment $environment, ?HasStatusLabel $status, string $enumClass): string | array
    {
        if (! $status instanceof HasStatusLabel) {
            return $this->getAllStatusLabels($environment, $enumClass);
        }

        return $this->renderStatusLabel($environment, $status);
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

    private function renderStatusLabel(Environment $environment, HasStatusLabel $status): string
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
                    'label' => $status->getVariant()->value,
                ],
            ]
        );
    }
}
