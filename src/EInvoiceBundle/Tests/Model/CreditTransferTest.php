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

namespace SolidInvoice\EInvoiceBundle\Tests\Model;

use Error;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\EInvoiceBundle\Model\CreditTransfer;

#[CoversClass(CreditTransfer::class)]
final class CreditTransferTest extends TestCase
{
    public function testConstructionWithAllProperties(): void
    {
        $transfer = new CreditTransfer(
            accountIdentifier: 'GB33BUKB20201555555555',
            accountName: 'ACME Corp',
            serviceProviderIdentifier: 'BUKBGB22',
        );

        self::assertSame('GB33BUKB20201555555555', $transfer->accountIdentifier);
        self::assertSame('ACME Corp', $transfer->accountName);
        self::assertSame('BUKBGB22', $transfer->serviceProviderIdentifier);
    }

    public function testOptionalPropertiesDefaultToNull(): void
    {
        $transfer = new CreditTransfer('GB33BUKB20201555555555');

        self::assertNull($transfer->accountName);
        self::assertNull($transfer->serviceProviderIdentifier);
    }

    public function testPropertiesAreReadonly(): void
    {
        $transfer = new CreditTransfer('GB33BUKB20201555555555');

        $this->expectException(Error::class);

        // @phpstan-ignore-next-line property.readOnly
        $transfer->accountIdentifier = 'GB00OTHER';
    }
}
