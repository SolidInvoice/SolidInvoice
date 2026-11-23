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

use JsonException;
use SolidInvoice\EInvoiceBundle\Model\Contact;
use SolidInvoice\EInvoiceBundle\Model\PostalAddress;
use SolidInvoice\EInvoiceBundle\Model\Seller;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * Maps BG-4/BG-5/BG-6 (the seller party) from {@see SystemConfig} settings rows.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\SellerMapperTest
 */
final readonly class SellerMapper
{
    public function __construct(
        private SystemConfig $systemConfig,
    ) {
    }

    public function map(): Seller
    {
        return new Seller(
            name: (string) $this->systemConfig->get('system/company/company_name'),
            vatIdentifier: $this->systemConfig->get('system/company/vat_number'),
            address: $this->decodeAddress($this->systemConfig->get('system/company/contact_details/address')),
            contact: $this->buildContact(),
        );
    }

    /**
     * The address setting is an unvalidated JSON blob (`AddressType::class`). An absent blob, an
     * empty string, invalid JSON, or valid JSON of the wrong shape all yield null here, never an
     * exception and never a partly-guessed address.
     */
    private function decodeAddress(?string $json): ?PostalAddress
    {
        if (null === $json || '' === $json) {
            return null;
        }

        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data) || array_is_list($data)) {
            return null;
        }

        foreach (['street1', 'street2', 'city', 'state', 'zip', 'country'] as $key) {
            if (isset($data[$key]) && ! is_string($data[$key])) {
                return null;
            }
        }

        $address = new PostalAddress(
            addressLine1: $data['street1'] ?? null,
            addressLine2: $data['street2'] ?? null,
            city: $data['city'] ?? null,
            postCode: $data['zip'] ?? null,
            countrySubdivision: $data['state'] ?? null,
            countryCode: $data['country'] ?? null,
        );

        return $this->isEmptyAddress($address) ? null : $address;
    }

    private function isEmptyAddress(PostalAddress $address): bool
    {
        return null === $address->addressLine1
            && null === $address->addressLine2
            && null === $address->addressLine3
            && null === $address->city
            && null === $address->postCode
            && null === $address->countrySubdivision
            && null === $address->countryCode;
    }

    /**
     * BT-41 (name) has no setting and is always null. An optional group is never an empty object
     * (design §4.1.6), so a contact with no telephone and no email maps to null rather than a
     * `Contact` carrying nothing.
     */
    private function buildContact(): ?Contact
    {
        $telephone = $this->systemConfig->get('system/company/contact_details/phone_number');
        $email = $this->systemConfig->get('system/company/contact_details/email');

        if (null === $telephone && null === $email) {
            return null;
        }

        return new Contact(telephone: $telephone, email: $email);
    }
}
