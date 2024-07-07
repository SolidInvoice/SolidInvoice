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

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Form\Type\ClientType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\PreloadedExtension;

class ClientTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $company = $this->faker->company;
        $url = $this->faker->url;
        $currencyCode = 'USD';

        $formData = [
            'name' => $company,
            'website' => $url,
            'currencyCode' => $currencyCode,
            'contacts' => [],
            'addresses' => [],
        ];

        $object = new Client();
        $object->setName($company);
        $object->setWebsite($url);
        $object->setCurrencyCode($currencyCode);

        $this->assertFormData(ClientType::class, $formData, $object);
    }

    /**
     * @return PreloadedExtension[]
     */
    protected function getExtensions(): array
    {
        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([new ContactDetailType(), new CurrencyType('en')], []),
        ];
    }
}
