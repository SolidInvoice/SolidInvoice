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

namespace SolidInvoice\InvoiceBundle\Tests\Validator\Constraints;

use Brick\Math\BigInteger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidInvoice\InvoiceBundle\Repository\CreditNoteRepository;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteWithinInvoiceTotal;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteWithinInvoiceTotalValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CreditNoteWithinInvoiceTotalValidator>
 */
#[CoversClass(CreditNoteWithinInvoiceTotal::class)]
#[CoversClass(CreditNoteWithinInvoiceTotalValidator::class)]
final class CreditNoteWithinInvoiceTotalValidatorTest extends ConstraintValidatorTestCase
{
    private Stub & CreditNoteRepository $creditNoteRepository;

    protected function createValidator(): CreditNoteWithinInvoiceTotalValidator
    {
        $this->creditNoteRepository = $this->createStub(CreditNoteRepository::class);

        return new CreditNoteWithinInvoiceTotalValidator($this->creditNoteRepository);
    }

    public function testNoViolationForAStandaloneCreditNote(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setTotal(10000);
        $creditNote->setStatus(CreditNoteStatus::Issued);

        $this->validator->validate($creditNote, new CreditNoteWithinInvoiceTotal());

        $this->assertNoViolation();
    }

    public function testNoViolationForACancelledCreditNoteEvenOverTheCap(): void
    {
        $invoice = new Invoice();
        $invoice->setTotal(10000);

        $creditNote = new CreditNote();
        $creditNote->setInvoice($invoice);
        $creditNote->setTotal(20000);
        $creditNote->setStatus(CreditNoteStatus::Cancelled);

        $this->validator->validate($creditNote, new CreditNoteWithinInvoiceTotal());

        $this->assertNoViolation();
    }

    public function testNoViolationAtTheBoundary(): void
    {
        $invoice = new Invoice();
        $invoice->setTotal(10000);

        $creditNote = new CreditNote();
        $creditNote->setInvoice($invoice);
        $creditNote->setTotal(4000);
        $creditNote->setStatus(CreditNoteStatus::Issued);

        $this->creditNoteRepository->method('getTotalCreditedForInvoice')->willReturn(BigInteger::of(6000));

        $this->validator->validate($creditNote, new CreditNoteWithinInvoiceTotal());

        $this->assertNoViolation();
    }

    public function testViolationJustOverTheBoundary(): void
    {
        $constraint = new CreditNoteWithinInvoiceTotal();

        $invoice = new Invoice();
        $invoice->setTotal(10000);

        $creditNote = new CreditNote();
        $creditNote->setInvoice($invoice);
        $creditNote->setTotal(4001);
        $creditNote->setStatus(CreditNoteStatus::Issued);

        $this->creditNoteRepository->method('getTotalCreditedForInvoice')->willReturn(BigInteger::of(6000));

        $this->validator->validate($creditNote, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.total')
            ->assertRaised();
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongConstraintTypeThrows(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new CreditNote(), $this->createStub(Constraint::class));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testWrongValueTypeThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate('not-a-credit-note', new CreditNoteWithinInvoiceTotal());
    }
}
