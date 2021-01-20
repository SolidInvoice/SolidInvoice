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

use Faker\Factory;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use Symfony\Component\Form\PreloadedExtension;

class ContactDetailTypeTest extends FormTestCase
{
    public function testSubmit()
    {
        $faker = Factory::create();

        $url = $faker->url;

        $formData = [
            'value' => $url,
            'type' => 1,
        ];

        $object = [
            'value' => $url,
            'type' => $this->registry->getRepository(ContactType::class)->find(1),
        ];

        $this->assertFormData(ContactDetailType::class, $formData, $object);
    }

    protected function getExtensions()
    {
        $type = new ContactDetailType($this->registry->getRepository(ContactType::class));

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }
}
