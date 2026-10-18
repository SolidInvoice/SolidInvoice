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

namespace SolidInvoice\PaymentBundle\Enum;

/**
 * The payment methods a company can offer on an invoice. Deliberately not the UNTDID 4461
 * code list: these are the choices a freelancer recognises, and SOL-87 maps them onto
 * EInvoiceBundle\Enum\PaymentMeansCode so the code list can change without a data migration.
 */
enum PaymentMeans: string
{
    case BankTransfer = 'bank_transfer';
    case SepaCreditTransfer = 'sepa_credit_transfer';
    case SepaDirectDebit = 'sepa_direct_debit';
    case DirectDebit = 'direct_debit';
    case StandingAgreement = 'standing_agreement';
    case Other = 'other';

    public function getLabel(): string
    {
        return 'payment_means.' . $this->value;
    }
}
