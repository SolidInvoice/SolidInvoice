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

namespace SolidInvoice\CoreBundle\Serializer\Normalizer;

use ArrayObject;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Service\ResetInterface;
use function in_array;
use function is_array;

/**
 * Fills the read-only `publicUrl`, `pdfUrl` and (invoices only) `payUrl` fields of the API reads. The entities
 * declare them as getters so they are documented in OpenAPI; the values need the router, so they are set here.
 * URLs are built like the emails build them: absolute, from the current request context or `default_uri`.
 */
#[AutoconfigureTag('serializer.normalizer')]
#[AutoconfigureTag('kernel.reset', ['method' => 'reset'])]
final class BillingLinksNormalizer implements NormalizerAwareInterface, NormalizerInterface, ResetInterface
{
    use NormalizerAwareTrait;

    private const string SKIP_KEY = self::class . '::skip';

    /**
     * Online payment method counts per company, so a collection page queries once rather than once per invoice.
     *
     * @var array<string, bool>
     */
    private array $canPayOnline = [];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly PaymentMethodRepository $paymentMethods,
    ) {
    }

    public function reset(): void
    {
        $this->canPayOnline = [];
    }

    /**
     * @param array<string, mixed> $context
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array | string | int | float | bool | ArrayObject | null
    {
        $context[self::SKIP_KEY] = true;
        $data = $this->normalizer->normalize($object, $format, $context);

        if (! is_array($data) || (! $object instanceof Invoice && ! $object instanceof Quote)) {
            return $data;
        }

        [$route, $uuid] = $object instanceof Invoice ? ['_view_invoice_external', $object->getUuid()] : ['_view_quote_external', $object->getUuid()];

        $data['publicUrl'] = $this->urlGenerator->generate($route, ['uuid' => $uuid->toString()], UrlGeneratorInterface::ABSOLUTE_URL);
        $data['pdfUrl'] = $this->urlGenerator->generate($route, ['uuid' => $uuid->toString(), '_format' => 'pdf'], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($object instanceof Invoice) {
            $data['payUrl'] = $this->canPayOnline($object)
                ? $this->urlGenerator->generate('_payments_create', ['uuid' => $uuid->toString()], UrlGeneratorInterface::ABSOLUTE_URL)
                : null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($context[self::SKIP_KEY] ?? false) {
            return false;
        }

        $groups = (array) ($context['groups'] ?? []);

        return ($data instanceof Invoice && in_array('invoice_api:read', $groups, true))
            || ($data instanceof Quote && in_array('quote_api:read', $groups, true));
    }

    /**
     * @return array<class-string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Invoice::class => false,
            Quote::class => false,
        ];
    }

    /**
     * Same rule as the "Pay now" button on the public view: at least one enabled method that is not internal
     * (offline methods such as cash are stored as internal).
     */
    private function canPayOnline(Invoice $invoice): bool
    {
        $key = (string) $invoice->getCompany()->getId();

        return $this->canPayOnline[$key] ??= $this->paymentMethods->getTotalMethodsConfigured(includeInternal: false) > 0;
    }
}
