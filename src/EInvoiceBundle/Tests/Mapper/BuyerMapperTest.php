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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\EInvoiceBundle\Mapper\BuyerMapper;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

final class BuyerMapperTest extends TestCase
{
    public function testMapsClientAddressAndContactToBuyer(): void
    {
        $client = new Client()->setName('Acme Ltd');

        $taxIdentifier = new TaxIdentifier()->setLabel('VAT')->setValue('GB123456789');
        $client->addTaxIdentifier($taxIdentifier);

        $address = new Address();
        $address->setStreet1('221B Baker Street');
        $address->setStreet2('Marylebone');
        $address->setCity('London');
        $address->setState('Greater London');
        $address->setZip('NW1 6XE');
        $address->setCountry('GB');

        $client->addAddress($address);

        $contact = new Contact()->setFirstName('John')->setLastName('Watson')->setEmail('john@acme.example');

        $buyer = new BuyerMapper()->map($client, new ArrayCollection([$contact]));

        self::assertSame('Acme Ltd', $buyer->name);
        self::assertSame('GB123456789', $buyer->vatIdentifier);
        self::assertNotNull($buyer->address);
        self::assertSame('221B Baker Street', $buyer->address->addressLine1);
        self::assertSame('Marylebone', $buyer->address->addressLine2);
        self::assertSame('London', $buyer->address->city);
        self::assertSame('Greater London', $buyer->address->countrySubdivision);
        self::assertSame('NW1 6XE', $buyer->address->postCode);
        self::assertSame('GB', $buyer->address->countryCode);
        self::assertNotNull($buyer->contact);
        self::assertSame('John Watson', $buyer->contact->name);
        self::assertSame('john@acme.example', $buyer->contact->email);
        // BT-57: ClientBundle\Entity\Contact carries no phone field.
        self::assertNull($buyer->contact->telephone);
    }

    public function testNoAddressOrContactYieldsNullGroups(): void
    {
        $client = new Client()->setName('Acme Ltd');

        $buyer = new BuyerMapper()->map($client, new ArrayCollection());

        self::assertNull($buyer->address);
        self::assertNull($buyer->contact);
        self::assertNull($buyer->vatIdentifier);
    }

    public function testEmptyAddressYieldsNullPostalAddress(): void
    {
        $client = new Client()->setName('Acme Ltd');
        $client->addAddress(new Address());

        $buyer = new BuyerMapper()->map($client, new ArrayCollection());

        self::assertNull($buyer->address);
    }
}
