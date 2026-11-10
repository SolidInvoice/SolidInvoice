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

namespace SolidInvoice\McpBundle\Mcp\Tool;

use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Carbon\WeekDay;
use DateTimeInterface;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BigIntegerNormalizer;
use SolidInvoice\ApiBundle\Serializer\Normalizer\MoneyCurrencyNormalizer;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ClientSummaryOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ContactOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\DiscountOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\InvoiceTaxOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\LineOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\LineTaxOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentMethodOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\PaymentOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\ProductOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\QuoteOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringInvoiceOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\RecurringScheduleOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxIdentifierOutput;
use SolidInvoice\McpBundle\Mcp\Output\Entity\TaxOutput;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use function array_map;
use function array_values;
use function sprintf;

/**
 * Builds the typed MCP output DTO for a business entity. Kept intentionally narrow: only fields
 * that AI agents legitimately need. Money is a decimal string in the document's currency, at
 * that currency's own scale.
 */
final readonly class EntityNormalizer
{
    public function __construct(
        private BigIntegerNormalizer $moneyNormalizer = new BigIntegerNormalizer(),
    ) {
    }

    public function normalize(object $entity): object
    {
        return match (true) {
            $entity instanceof Invoice => $this->invoice($entity),
            $entity instanceof RecurringInvoice => $this->recurringInvoice($entity),
            $entity instanceof Quote => $this->quote($entity),
            $entity instanceof Client => $this->client($entity),
            $entity instanceof Contact => $this->contact($entity),
            $entity instanceof Payment => $this->payment($entity),
            $entity instanceof PaymentMethod => $this->paymentMethod($entity),
            $entity instanceof Tax => $this->tax($entity),
            $entity instanceof Product => $this->product($entity),
            $entity instanceof TaxIdentifier => $this->taxIdentifier($entity),
            default => throw new ToolCallException(sprintf(
                'Unsupported entity type "%s". Add a dedicated output DTO before exposing it via MCP.',
                $entity::class,
            )),
        };
    }

    /**
     * @param iterable<object> $entities
     *
     * @return list<object>
     */
    public function normalizeMany(iterable $entities): array
    {
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $this->normalize($entity);
        }

        return $result;
    }

    public function invoice(Invoice $invoice): InvoiceOutput
    {
        $discount = $invoice->getDiscount();
        $client = $invoice->getClient();
        $currency = $invoice->getCurrencyCode() ?? $client?->getCurrency()->getCode();

        return new InvoiceOutput(
            id: $invoice->getId()?->toBase58(),
            invoice_number: $invoice->getInvoiceId(),
            status: $invoice->getStatus(),
            client: $this->clientSummary($client),
            currency: $currency,
            total: $this->money($invoice->getTotal(), $currency),
            base_total: $this->money($invoice->getBaseTotal(), $currency),
            balance: $this->money($invoice->getBalance(), $currency),
            tax: $this->money($invoice->getTax(), $currency),
            withholding_amount: $this->money($invoice->getWithholdingAmount(), $currency),
            amount_payable: $this->money($invoice->getPayableAmount(), $currency),
            discount: new DiscountOutput(
                type: $discount->getType(),
                value_money: $this->money($discount->getValueMoney(), $currency),
                value_percentage: $discount->getValuePercentage(),
            ),
            invoice_date: $invoice->getInvoiceDate()->format(DateTimeInterface::ATOM),
            due: $this->date($invoice->getDue()),
            payment_terms: $invoice->getPaymentTerms(),
            paid_date: $this->date($invoice->getPaidDate()),
            created: $this->date($invoice->getCreated()),
            terms: $invoice->getTerms(),
            notes: $invoice->getNotes(),
            lines: $this->lines($invoice->getLines()->toArray(), $currency),
            invoice_taxes: $this->invoiceTaxes($invoice->getInvoiceTaxes()->toArray(), $currency),
        );
    }

    public function recurringInvoice(RecurringInvoice $invoice): RecurringInvoiceOutput
    {
        $options = $invoice->getRecurringOptions();
        $client = $invoice->getClient();
        $currency = $invoice->getCurrencyCode() ?? $client?->getCurrency()->getCode();

        return new RecurringInvoiceOutput(
            id: $invoice->getId()?->toBase58(),
            status: $invoice->getStatus(),
            client: $this->clientSummary($client),
            currency: $currency,
            total: $this->money($invoice->getTotal(), $currency),
            base_total: $this->money($invoice->getBaseTotal(), $currency),
            tax: $this->money($invoice->getTax(), $currency),
            withholding_amount: $this->money($invoice->getWithholdingAmount(), $currency),
            amount_payable: $this->money($invoice->getPayableAmount(), $currency),
            date_start: $this->date($invoice->getDateStart()),
            date_end: $this->date($invoice->getDateEnd()),
            schedule: new RecurringScheduleOutput(
                type: $options->getType(),
                days: array_values(array_map(static fn (WeekDay | int $day): int => $day instanceof WeekDay ? $day->value : $day, $options->getDays())),
                end_type: $options->getEndType(),
                end_date: $this->date($options->getEndDate()),
                end_occurrence: $options->getEndOccurrence(),
            ),
            generated_invoice_count: $invoice->getInvoices()->count(),
            payment_terms: $invoice->getPaymentTerms(),
            terms: $invoice->getTerms(),
            notes: $invoice->getNotes(),
            created: $this->date($invoice->getCreated()),
            lines: $this->lines($invoice->getLines()->toArray(), $currency),
            invoice_taxes: $this->invoiceTaxes($invoice->getInvoiceTaxes()->toArray(), $currency),
        );
    }

    public function quote(Quote $quote): QuoteOutput
    {
        $client = $quote->getClient();
        $currency = $quote->getCurrencyCode() ?? $client?->getCurrency()->getCode();

        return new QuoteOutput(
            id: $quote->getId()?->toBase58(),
            quote_number: $quote->getQuoteId(),
            status: $quote->getStatus(),
            client: $this->clientSummary($client),
            currency: $currency,
            total: $this->money($quote->getTotal(), $currency),
            base_total: $this->money($quote->getBaseTotal(), $currency),
            tax: $this->money($quote->getTax(), $currency),
            withholding_amount: $this->money($quote->getWithholdingAmount(), $currency),
            amount_payable: $this->money($quote->getPayableAmount(), $currency),
            due: $this->date($quote->getDue()),
            created: $this->date($quote->getCreated()),
            terms: $quote->getTerms(),
            notes: $quote->getNotes(),
            lines: $this->lines($quote->getLines()->toArray(), $currency),
            invoice_taxes: $this->invoiceTaxes($quote->getInvoiceTaxes()->toArray(), $currency),
        );
    }

    public function client(Client $client): ClientOutput
    {
        return new ClientOutput(
            id: $client->getId()?->toBase58(),
            name: $client->getName(),
            status: $client->getStatus(),
            website: $client->getWebsite(),
            currency: $client->getCurrency()->getCode(),
            tax_identifiers: array_values(array_map(
                $this->taxIdentifier(...),
                $client->getTaxIdentifiers()->toArray(),
            )),
            created: $this->date($client->getCreated()),
        );
    }

    public function taxIdentifier(TaxIdentifier $taxIdentifier): TaxIdentifierOutput
    {
        return new TaxIdentifierOutput(
            id: $taxIdentifier->getId()?->toBase58(),
            label: $taxIdentifier->getLabel(),
            value: $taxIdentifier->getValue(),
            primary: $taxIdentifier->isPrimary(),
        );
    }

    public function contact(Contact $contact): ContactOutput
    {
        return new ContactOutput(
            id: $contact->getId()?->toBase58(),
            first_name: $contact->getFirstName(),
            last_name: $contact->getLastName(),
            email: $contact->getEmail(),
            client: $this->clientSummary($contact->getClient()),
        );
    }

    public function payment(Payment $payment): PaymentOutput
    {
        $amount = $payment->getAmount();
        $currency = $amount->getCurrency()->getCode();

        return new PaymentOutput(
            id: $payment->getId()?->toBase58(),
            status: $payment->getStatus(),
            method: $payment->getMethod()?->getName(),
            amount: $this->money(BigInteger::of($amount->getAmount()), $currency),
            currency: $currency,
            invoice_id: $payment->getInvoice()?->getId()?->toBase58(),
            client_id: $payment->getClient()?->getId()?->toBase58(),
            created: $this->date($payment->getCreated()),
            completed: $this->date($payment->getCompleted()),
        );
    }

    public function paymentMethod(PaymentMethod $method): PaymentMethodOutput
    {
        return new PaymentMethodOutput(
            id: $method->getId()?->toBase58(),
            name: $method->getName(),
            gateway_name: $method->getGatewayName(),
            enabled: $method->isEnabled(),
        );
    }

    public function product(Product $product): ProductOutput
    {
        $tax = $product->getTax();

        return new ProductOutput(
            id: $product->getId()?->toBase58(),
            name: $product->getName(),
            description: $product->getDescription(),
            price: (string) $product->getPrice()->strippedOfTrailingZeros(),
            quantity: (string) $product->getQuantity()->strippedOfTrailingZeros(),
            tax: $tax instanceof Tax ? $this->tax($tax) : null,
            created: $this->date($product->getCreated()),
            updated: $this->date($product->getUpdated()),
        );
    }

    public function tax(Tax $tax): TaxOutput
    {
        return new TaxOutput(
            id: $tax->getId()->toBase58(),
            name: $tax->getName(),
            rate: $tax->getRate(),
            type: $tax->getType(),
            category: $tax->getCategory(),
            compound: $tax->isCompound(),
        );
    }

    /**
     * A stored minor-unit amount as a decimal string in $currency, at its own scale. The same
     * conversion the REST API applies to decimal money.
     */
    public function money(BigNumber $amount, ?string $currency): string
    {
        return (string) $this->moneyNormalizer->normalize($amount, null, [
            ApiVersion::DECIMAL_MONEY => true,
            MoneyCurrencyNormalizer::CURRENCY => $currency,
        ]);
    }

    /**
     * @param list<LineInterface> $lines
     *
     * @return list<LineOutput>
     */
    private function lines(array $lines, ?string $currency): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = new LineOutput(
                id: $line->getId()->toBase58(),
                name: $line->getName(),
                description: $line->getDescription(),
                price: $this->money($line->getPrice(), $currency),
                qty: (string) $line->getQty(),
                total: $this->money($line->getTotal(), $currency),
                taxes: array_values(array_map(
                    fn (LineTax $lineTax): LineTaxOutput => $this->lineTax($lineTax, $currency),
                    $line->getTaxes()->toArray(),
                )),
            );
        }

        return $result;
    }

    /**
     * @param list<InvoiceTax> $invoiceTaxes
     *
     * @return list<InvoiceTaxOutput>
     */
    private function invoiceTaxes(array $invoiceTaxes, ?string $currency): array
    {
        $rows = [];

        foreach ($invoiceTaxes as $invoiceTax) {
            $rows[] = new InvoiceTaxOutput(
                id: $invoiceTax->getId()?->toBase58(),
                tax_id: $invoiceTax->getTax()?->getId()->toBase58(),
                name: $invoiceTax->getNameSnapshot(),
                rate: $invoiceTax->getRateSnapshot(),
                category: $invoiceTax->getCategorySnapshot(),
                direction: $invoiceTax->getDirection(),
                sequence: $invoiceTax->getSequence(),
                amount: $this->money($invoiceTax->getAmount(), $currency),
                note: $invoiceTax->getNote(),
            );
        }

        return $rows;
    }

    private function lineTax(LineTax $lineTax, ?string $currency): LineTaxOutput
    {
        return new LineTaxOutput(
            id: $lineTax->getId()?->toBase58(),
            tax_id: $lineTax->getTax()?->getId()->toBase58(),
            name: $lineTax->getNameSnapshot(),
            rate: $lineTax->getRateSnapshot(),
            type: $lineTax->getTypeSnapshot(),
            category: $lineTax->getCategorySnapshot(),
            compound: $lineTax->isCompound(),
            sequence: $lineTax->getSequence(),
            amount: $this->money($lineTax->getAmount(), $currency),
        );
    }

    private function clientSummary(?Client $client): ?ClientSummaryOutput
    {
        if (! $client instanceof Client) {
            return null;
        }

        return new ClientSummaryOutput(
            id: $client->getId()?->toBase58(),
            name: $client->getName(),
            currency: $client->getCurrency()->getCode(),
        );
    }

    private function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
