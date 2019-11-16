<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Tests\Form\Type;

use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use Faker\Factory;
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
