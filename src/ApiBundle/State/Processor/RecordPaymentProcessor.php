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

namespace SolidInvoice\ApiBundle\State\Processor;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use LogicException;
use SolidInvoice\ApiBundle\DTO\RecordPaymentInput;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Exception\InvalidPaymentException;
use SolidInvoice\PaymentBundle\Manager\ManualPaymentManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;
use Symfony\Contracts\Translation\TranslatorInterface;
use function is_float;
use function is_int;
use function sprintf;

/**
 * @implements ProcessorInterface<RecordPaymentInput, Payment>
 * @see \SolidInvoice\ApiBundle\Tests\State\Processor\RecordPaymentProcessorTest
 * @see \SolidInvoice\PaymentBundle\Tests\Functional\Api\RecordPaymentTest
 */
final readonly class RecordPaymentProcessor implements ProcessorInterface
{
    /**
     * Where the integer (minor unit) amount is documented as deprecated.
     */
    public const string LEGACY_AMOUNT_DOCS = 'https://solidinvoice.co/docs/api/rest-api#recording-a-payment';

    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private ManualPaymentManager $paymentManager,
        private CurrencyScale $currencyScale,
        private TranslatorInterface $translator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Payment
    {
        assert($data instanceof RecordPaymentInput);

        $invoiceId = $uriVariables['invoiceId'] ?? null;

        $invoice = $this->invoiceRepository->findOneBy(['id' => $invoiceId]);

        if (! $invoice instanceof Invoice) {
            throw new NotFoundHttpException(sprintf('Invoice "%s" not found.', $invoiceId));
        }

        $request = $context['request'] ?? null;

        // A JSON integer is the original minor-unit form, still accepted for one deprecation cycle.
        if (is_int($data->amount) && $request instanceof Request && $operation instanceof HttpOperation) {
            $request->attributes->set('_api_operation', $operation->withHeaders([...($operation->getHeaders() ?? []), 'Deprecation' => 'true']));

            // API Platform builds the Link header from this provider, so a Link set on the operation is lost.
            $links = $request->attributes->get('_api_platform_links') ?? new GenericLinkProvider();
            $request->attributes->set('_api_platform_links', $links->withLink(new Link('deprecation', self::LEGACY_AMOUNT_DOCS)));
        }

        try {
            return $this->paymentManager->record(
                $invoice,
                $this->minorAmount($data->amount, $invoice),
                $data->currency,
                $data->method,
                $data->paidAt === null ? null : new CarbonImmutable($data->paidAt, 'UTC'),
                $data->reference,
                $data->notes,
            );
        } catch (InvalidPaymentException $exception) {
            throw $this->violation($exception->field, $exception->getMessage(), null);
        }
    }

    private function minorAmount(string | int | float | null $amount, Invoice $invoice): BigInteger
    {
        if (is_int($amount)) {
            return BigInteger::of($amount);
        }

        try {
            // As BigIntegerNormalizer does: pin a JSON float's string form so it is host-independent.
            $major = BigDecimal::of(is_float($amount) ? sprintf('%.14G', $amount) : (string) $amount);
        } catch (MathException) {
            throw $this->violation('amount', $this->translator->trans('payment.record.error.amount_invalid'), $amount);
        }

        try {
            $currency = $invoice->getCurrency();
        } catch (LogicException) {
            throw $this->violation('invoice', $this->translator->trans('payment.record.error.no_client'), null);
        }

        $minor = $this->currencyScale->toMinorUnit($major, $currency);

        // Rejected rather than rounded: a silently rounded payment is a wrong payment.
        if ($minor->strippedOfTrailingZeros()->getScale() > 0) {
            throw $this->violation('amount', $this->translator->trans('payment.record.error.amount_too_precise', ['%currency%' => $currency->getCode()]), $amount);
        }

        return $minor->toBigInteger();
    }

    private function violation(string $field, string $message, mixed $invalidValue): ValidationException
    {
        return new ValidationException(new ConstraintViolationList([
            new ConstraintViolation($message, null, [], null, $field, $invalidValue),
        ]));
    }
}
