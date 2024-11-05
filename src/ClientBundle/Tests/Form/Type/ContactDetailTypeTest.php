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
use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\ClientBundle\Form\Type\ContactDetailType;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;

class ContactDetailTypeTest extends FormTestCase
{
    use EnsureApplicationInstalled;

    public function testSubmit(): void
    {
        $faker = Factory::create();

        $url = $faker->url;
        $type = $this->registry->getRepository(ContactType::class)->findOneBy([]);

        $formData = [
            'value' => $url,
            'type' => $type->getId()->toString(),
        ];

        $object = (new AdditionalContactDetail())
            ->setType($type)
            ->setValue($url);

        $this->assertFormData(ContactDetailType::class, $formData, $object);
    }

    /**
     * @return FormExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([
                new ContactDetailType(),
            ], []),
        ];
    }
}
