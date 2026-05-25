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

use BackedEnum;
use Brick\Math\BigNumber;
use DateTimeInterface;
use Mcp\Exception\ToolCallException;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

/**
 * Normalises business entities to plain arrays suitable for MCP tool results.
 * Kept intentionally narrow — only fields that AI agents legitimately need.
 */
final class EntityNormalizer
{
    /**
     * @return array<string, mixed>
     */
    public function normalize(object $entity): array
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
            $entity instanceof TaxIdentifier => $this->taxIdentifier($entity),
            default => throw new ToolCallException(sprintf(
                'Unsupported entity type "%s". Add a dedicated serializer before exposing it via MCP.',
                $entity::class,
            )),
        };
    }

    /**
     * @param iterable<object> $entities
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeMany(iterable $entities): array
    {
        $result = [];

        foreach ($entities as $entity) {
            $result[] = $this->normalize($entity);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function invoice(Invoice $invoice): array
    {
        $discount = $invoice->getDiscount();
        $currency = $invoice->getClient()?->getCurrencyCode();

        return [
            'id' => $invoice->getId()?->toRfc4122(),
            'invoice_number' => $invoice->getInvoiceId(),
            'status' => $invoice->getStatus()?->value,
            'client' => $this->clientSummary($invoice->getClient()),
            'currency' => $currency,
            'total' => $this->bigNumber($invoice->getTotal()),
            'base_total' => $this->bigNumber($invoice->getBaseTotal()),
            'balance' => $this->bigNumber($invoice->getBalance()),
            'tax' => $this->bigNumber($invoice->getTax()),
            'withholding_amount' => $this->bigNumber($invoice->getWithholdingAmount()),
            'amount_payable' => $this->bigNumber($invoice->getPayableAmount()),
            'discount' => [
                'type' => $discount->getType(),
                'value_money' => $this->bigNumber($discount->getValueMoney()),
                'value_percentage' => $discount->getValuePercentage(),
            ],
            'invoice_date' => $this->date($invoice->getInvoiceDate()),
            'due' => $this->date($invoice->getDue()),
            'paid_date' => $this->date($invoice->getPaidDate()),
            'created' => $this->date($invoice->getCreated()),
            'terms' => $invoice->getTerms(),
            'notes' => $invoice->getNotes(),
            'lines' => $this->lines($invoice->getLines()->toArray()),
            'invoice_taxes' => $this->invoiceTaxes($invoice->getInvoiceTaxes()->toArray()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function recurringInvoice(RecurringInvoice $invoice): array
    {
        $options = $invoice->getRecurringOptions();

        return [
            'id' => $invoice->getId()?->toRfc4122(),
            'status' => $invoice->getStatus()?->value,
            'client' => $this->clientSummary($invoice->getClient()),
            'currency' => $invoice->getClient()?->getCurrencyCode(),
            'total' => $this->bigNumber($invoice->getTotal()),
            'base_total' => $this->bigNumber($invoice->getBaseTotal()),
            'tax' => $this->bigNumber($invoice->getTax()),
            'withholding_amount' => $this->bigNumber($invoice->getWithholdingAmount()),
            'amount_payable' => $this->bigNumber($invoice->getPayableAmount()),
            'date_start' => $this->date($invoice->getDateStart()),
            'date_end' => $this->date($invoice->getDateEnd()),
            'schedule' => [
                'type' => $options->getType()->value,
                'days' => $options->getDays(),
                'end_type' => $options->getEndType()->value,
                'end_date' => $this->date($options->getEndDate()),
                'end_occurrence' => $options->getEndOccurrence(),
            ],
            'generated_invoice_count' => $invoice->getInvoices()->count(),
            'terms' => $invoice->getTerms(),
            'notes' => $invoice->getNotes(),
            'created' => $this->date($invoice->getCreated()),
            'lines' => $this->lines($invoice->getLines()->toArray()),
            'invoice_taxes' => $this->invoiceTaxes($invoice->getInvoiceTaxes()->toArray()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quote(Quote $quote): array
    {
        return [
            'id' => $quote->getId()?->toRfc4122(),
            'quote_number' => $quote->getQuoteId(),
            'status' => $quote->getStatus()?->value,
            'client' => $this->clientSummary($quote->getClient()),
            'currency' => $quote->getClient()?->getCurrencyCode(),
            'total' => $this->bigNumber($quote->getTotal()),
            'base_total' => $this->bigNumber($quote->getBaseTotal()),
            'tax' => $this->bigNumber($quote->getTax()),
            'withholding_amount' => $this->bigNumber($quote->getWithholdingAmount()),
            'amount_payable' => $this->bigNumber($quote->getPayableAmount()),
            'due' => $this->date($quote->getDue()),
            'created' => $this->date($quote->getCreated()),
            'terms' => $quote->getTerms(),
            'notes' => $quote->getNotes(),
            'lines' => $this->lines($quote->getLines()->toArray()),
            'invoice_taxes' => $this->invoiceTaxes($quote->getInvoiceTaxes()->toArray()),
        ];
    }

    /**
     * @param list<LineInterface> $lines
     *
     * @return list<array<string, mixed>>
     */
    private function lines(array $lines): array
    {
        $result = [];

        foreach ($lines as $line) {
            $result[] = [
                'id' => $line->getId()->toRfc4122(),
                'description' => $line->getDescription(),
                'price' => $this->bigNumber($line->getPrice()),
                'qty' => $line->getQty(),
                'total' => $this->bigNumber($line->getTotal()),
                'taxes' => array_values(array_map(
                    $this->lineTax(...),
                    $line->getTaxes()->toArray(),
                )),
            ];
        }

        return $result;
    }

    /**
     * @param list<InvoiceTax> $invoiceTaxes
     *
     * @return list<array<string, mixed>>
     */
    private function invoiceTaxes(array $invoiceTaxes): array
    {
        $rows = [];

        foreach ($invoiceTaxes as $invoiceTax) {
            $rows[] = [
                'id' => $invoiceTax->getId()?->toRfc4122(),
                'tax_id' => $invoiceTax->getTax()?->getId()?->toRfc4122(),
                'name' => $invoiceTax->getNameSnapshot(),
                'rate' => $invoiceTax->getRateSnapshot(),
                'category' => $invoiceTax->getCategorySnapshot()->value,
                'direction' => $invoiceTax->getDirection()->value,
                'sequence' => $invoiceTax->getSequence(),
                'amount' => $this->bigNumber($invoiceTax->getAmount()),
                'note' => $invoiceTax->getNote(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function lineTax(LineTax $lineTax): array
    {
        return [
            'id' => $lineTax->getId()?->toRfc4122(),
            'tax_id' => $lineTax->getTax()?->getId()?->toRfc4122(),
            'name' => $lineTax->getNameSnapshot(),
            'rate' => $lineTax->getRateSnapshot(),
            'type' => $lineTax->getTypeSnapshot()->value,
            'category' => $lineTax->getCategorySnapshot()->value,
            'compound' => $lineTax->isCompound(),
            'sequence' => $lineTax->getSequence(),
            'amount' => $this->bigNumber($lineTax->getAmount()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function client(Client $client): array
    {
        $status = $client->getStatus();

        return [
            'id' => $client->getId()?->toRfc4122(),
            'name' => $client->getName(),
            'status' => $status instanceof BackedEnum ? $status->value : $status,
            'website' => $client->getWebsite(),
            'currency' => $client->getCurrencyCode(),
            'tax_identifiers' => array_values(array_map(
                $this->taxIdentifier(...),
                $client->getTaxIdentifiers()->toArray(),
            )),
            'created' => $this->date($client->getCreated()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taxIdentifier(TaxIdentifier $taxIdentifier): array
    {
        return [
            'id' => $taxIdentifier->getId()?->toRfc4122(),
            'label' => $taxIdentifier->getLabel(),
            'value' => $taxIdentifier->getValue(),
            'primary' => $taxIdentifier->isPrimary(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function clientSummary(?Client $client): ?array
    {
        if (! $client instanceof Client) {
            return null;
        }

        return [
            'id' => $client->getId()?->toRfc4122(),
            'name' => $client->getName(),
            'currency' => $client->getCurrencyCode(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contact(Contact $contact): array
    {
        return [
            'id' => $contact->getId()?->toRfc4122(),
            'first_name' => $contact->getFirstName(),
            'last_name' => $contact->getLastName(),
            'email' => $contact->getEmail(),
            'client' => $this->clientSummary($contact->getClient()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(Payment $payment): array
    {
        $status = $payment->getStatus();

        return [
            'id' => $payment->getId()?->toRfc4122(),
            'status' => $status instanceof BackedEnum ? $status->value : $status,
            'method' => $payment->getMethod()?->getName(),
            'amount' => $this->money($payment->getAmount()),
            'invoice_id' => $payment->getInvoice()?->getId()?->toRfc4122(),
            'client_id' => $payment->getClient()?->getId()?->toRfc4122(),
            'created' => $this->date($payment->getCreated()),
            'completed' => $this->date($payment->getCompleted()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentMethod(PaymentMethod $method): array
    {
        return [
            'id' => $method->getId()?->toRfc4122(),
            'name' => $method->getName(),
            'gateway_name' => $method->getGatewayName(),
            'enabled' => $method->isEnabled(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function tax(Tax $tax): array
    {
        return [
            'id' => $tax->getId()->toRfc4122(),
            'name' => $tax->getName(),
            'rate' => $tax->getRate(),
            'type' => $tax->getType(),
            'category' => $tax->getCategory()->value,
            'compound' => $tax->isCompound(),
        ];
    }

    /**
     * @return array{amount: string, currency: string}|null
     */
    private function money(?Money $money): ?array
    {
        if (! $money instanceof Money) {
            return null;
        }

        return [
            'amount' => $money->getAmount(),
            'currency' => $money->getCurrency()->getCode(),
        ];
    }

    private function bigNumber(?BigNumber $number): ?string
    {
        return $number?->__toString();
    }

    private function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(DateTimeInterface::ATOM);
    }
}
