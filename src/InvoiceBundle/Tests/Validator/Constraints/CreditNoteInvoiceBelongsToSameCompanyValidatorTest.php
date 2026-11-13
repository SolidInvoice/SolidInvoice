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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompany;
use SolidInvoice\InvoiceBundle\Validator\Constraints\CreditNoteInvoiceBelongsToSameCompanyValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CreditNoteInvoiceBelongsToSameCompanyValidator>
 */
#[CoversClass(CreditNoteInvoiceBelongsToSameCompany::class)]
#[CoversClass(CreditNoteInvoiceBelongsToSameCompanyValidator::class)]
final class CreditNoteInvoiceBelongsToSameCompanyValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): CreditNoteInvoiceBelongsToSameCompanyValidator
    {
        return new CreditNoteInvoiceBelongsToSameCompanyValidator();
    }

    public function testNoViolationForAStandaloneCreditNote(): void
    {
        $creditNote = new CreditNote();
        $creditNote->setCompany(new Company());

        $this->validator->validate($creditNote, new CreditNoteInvoiceBelongsToSameCompany());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenInvoiceBelongsToTheSameCompany(): void
    {
        $company = new Company();

        $invoice = new Invoice();
        $invoice->setCompany($company);

        $creditNote = new CreditNote();
        $creditNote->setCompany($company);
        $creditNote->setInvoice($invoice);

        $this->validator->validate($creditNote, new CreditNoteInvoiceBelongsToSameCompany());

        $this->assertNoViolation();
    }

    public function testViolationWhenInvoiceBelongsToAnotherCompany(): void
    {
        $constraint = new CreditNoteInvoiceBelongsToSameCompany();

        $invoice = new Invoice();
        $invoice->setCompany(new Company());

        $creditNote = new CreditNote();
        $creditNote->setCompany(new Company());
        $creditNote->setInvoice($invoice);

        $this->validator->validate($creditNote, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.invoice')
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

        $this->validator->validate('not-a-credit-note', new CreditNoteInvoiceBelongsToSameCompany());
    }
}
