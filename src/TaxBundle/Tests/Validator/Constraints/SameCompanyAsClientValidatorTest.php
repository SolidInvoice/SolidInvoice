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

namespace SolidInvoice\TaxBundle\Tests\Validator\Constraints;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidInvoice\TaxBundle\Validator\Constraints\SameCompanyAsClient;
use SolidInvoice\TaxBundle\Validator\Constraints\SameCompanyAsClientValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<SameCompanyAsClientValidator>
 */
final class SameCompanyAsClientValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SameCompanyAsClientValidator
    {
        return new SameCompanyAsClientValidator();
    }

    public function testNullIsValid(): void
    {
        $this->validator->validate(null, new SameCompanyAsClient());

        $this->assertNoViolation();
    }

    public function testIdentifierWithoutClientIsValid(): void
    {
        $this->validator->validate(new TaxIdentifier(), new SameCompanyAsClient());

        $this->assertNoViolation();
    }

    public function testUnpersistedClientWithoutCompanyDoesNotFail(): void
    {
        $client = new Client();
        $identifier = new TaxIdentifier();
        $client->addTaxIdentifier($identifier);

        $this->validator->validate($identifier, new SameCompanyAsClient());

        $this->assertNoViolation();
    }
}
