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

namespace SolidInvoice\InvoiceBundle\DTO;

use SolidInvoice\InvoiceBundle\Enum\CreditFromInvoiceMode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Backs {@see \SolidInvoice\InvoiceBundle\Form\Type\CreditFromInvoiceType} — the
 * full-or-partial picker on the "Credit this invoice" flow. Not an entity: the amount here
 * only ever becomes a single {@see \SolidInvoice\InvoiceBundle\Entity\CreditNoteLine} once
 * {@see \SolidInvoice\InvoiceBundle\Action\CreditNote\CreateFromInvoice} resolves the mode.
 */
final class CreditFromInvoiceData
{
    public CreditFromInvoiceMode $mode = CreditFromInvoiceMode::Full;

    /**
     * Only read when {@see self::$mode} is {@see CreditFromInvoiceMode::Partial}.
     */
    #[Assert\PositiveOrZero]
    public string $amount = '0';

    public ?string $reason = null;
}
