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

namespace SolidInvoice\EInvoiceBundle\Tests\Validator\Constraints;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;
use SolidInvoice\EInvoiceBundle\Validator\Constraints\ExactlyOneSourceDocument;
use SolidInvoice\EInvoiceBundle\Validator\Constraints\ExactlyOneSourceDocumentValidator;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ExactlyOneSourceDocumentValidator>
 */
#[CoversClass(ExactlyOneSourceDocumentValidator::class)]
final class ExactlyOneSourceDocumentValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ExactlyOneSourceDocumentValidator
    {
        return new ExactlyOneSourceDocumentValidator();
    }

    public function testZeroOwnersRaisesViolation(): void
    {
        $document = $this->createDocument(null, null);

        $constraint = new ExactlyOneSourceDocument();
        $this->validator->validate($document, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testTwoOwnersRaisesViolation(): void
    {
        $document = $this->createDocument(new Invoice(), new CreditNote());

        $constraint = new ExactlyOneSourceDocument();
        $this->validator->validate($document, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testExactlyOneInvoiceOwnerPasses(): void
    {
        $document = $this->createDocument(new Invoice(), null);

        $this->validator->validate($document, new ExactlyOneSourceDocument());

        $this->assertNoViolation();
    }

    public function testExactlyOneCreditNoteOwnerPasses(): void
    {
        $document = $this->createDocument(null, new CreditNote());

        $this->validator->validate($document, new ExactlyOneSourceDocument());

        $this->assertNoViolation();
    }

    /**
     * `EInvoiceDocument` has no form and takes its source as a constructor argument, so — unlike
     * `ExactlyOneDocumentValidator` on `InvoiceTax` — it has no window in which an in-flight,
     * unpersisted row legitimately has no owner. A count other than 1 is always a violation here.
     */
    public function testZeroOwnersOnAnUnpersistedRowStillRaisesViolation(): void
    {
        $document = $this->createDocument(null, null);

        self::assertNull($document->getId());

        $constraint = new ExactlyOneSourceDocument();
        $this->validator->validate($document, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    private function createDocument(?Invoice $invoice, ?CreditNote $creditNote): EInvoiceDocument
    {
        return new EInvoiceDocument(
            new Company(),
            $invoice,
            $creditNote,
            'INV-0001',
            'peppol',
            'peppol-bis-billing-3.0',
            '<Invoice></Invoice>',
            SyntaxFormat::Ubl,
            'application/xml',
            'invoice-ubl.xml',
        );
    }
}
