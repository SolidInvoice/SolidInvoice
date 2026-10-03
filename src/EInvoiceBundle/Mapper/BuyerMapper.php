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

namespace SolidInvoice\EInvoiceBundle\Mapper;

use Doctrine\Common\Collections\Collection;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact as ClientContact;
use SolidInvoice\EInvoiceBundle\Model\Buyer;
use SolidInvoice\EInvoiceBundle\Model\Contact;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;

/**
 * Maps BG-7/BG-8/BG-9 (the buyer party) from {@see Client}, its first {@see Address} and the
 * invoice's first {@see ClientContact}.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\BuyerMapperTest
 */
final readonly class BuyerMapper
{
    /**
     * @param Collection<int, ClientContact> $invoiceContacts the invoice's own {@see Invoice::getUsers()}, not the client's full contact list
     */
    public function map(Client $client, Collection $invoiceContacts): Buyer
    {
        return new Buyer(
            name: (string) $client->getName(),
            vatIdentifier: $this->firstTaxIdentifierValue($client->getTaxIdentifiers()),
            address: $this->mapAddress($this->firstAddress($client->getAddresses())),
            // BT-57 telephone has no source: ClientBundle\Entity\Contact carries no phone field.
            contact: $this->mapContact($this->firstContact($invoiceContacts)),
        );
    }

    /**
     * The entity permits several addresses with no type discriminator; take the first by id,
     * since the collection carries no explicit order (design §5.4, BT-50…BT-55).
     *
     * @param Collection<int, Address> $addresses
     */
    private function firstAddress(Collection $addresses): ?Address
    {
        $items = $addresses->toArray();
        usort($items, static fn (Address $a, Address $b): int => (string) $a->getId() <=> (string) $b->getId());

        return $items[0] ?? null;
    }

    /**
     * @param Collection<int, ClientContact> $contacts
     */
    private function firstContact(Collection $contacts): ?ClientContact
    {
        $items = $contacts->toArray();
        usort($items, static fn (ClientContact $a, ClientContact $b): int => (string) $a->getId() <=> (string) $b->getId());

        return $items[0] ?? null;
    }

    /**
     * @param Collection<int, TaxIdentifier> $taxIdentifiers
     */
    private function firstTaxIdentifierValue(Collection $taxIdentifiers): ?string
    {
        $items = $taxIdentifiers->toArray();
        usort($items, static fn (TaxIdentifier $a, TaxIdentifier $b): int => (string) $a->getId() <=> (string) $b->getId());

        return ($items[0] ?? null)?->getValue();
    }

    private function mapAddress(?Address $address): ?PostalAddress
    {
        if (! $address instanceof Address || $address->isEmpty()) {
            return null;
        }

        return new PostalAddress(
            addressLine1: $address->getStreet1(),
            addressLine2: $address->getStreet2(),
            city: $address->getCity(),
            postCode: $address->getZip(),
            countrySubdivision: $address->getState(),
            countryCode: $address->getCountry(),
        );
    }

    private function mapContact(?ClientContact $contact): ?Contact
    {
        if (! $contact instanceof ClientContact) {
            return null;
        }

        $name = trim(sprintf('%s %s', $contact->getFirstName() ?? '', $contact->getLastName() ?? ''));

        return new Contact(
            name: '' === $name ? null : $name,
            email: $contact->getEmail(),
        );
    }
}
