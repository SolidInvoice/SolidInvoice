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

use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use Override;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Form\Type\ContactType;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use SolidInvoice\CoreBundle\Tests\FormTestCase;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\PreloadedExtension;

final class ContactTypeTest extends FormTestCase
{
    public function testSubmit(): void
    {
        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;
        $email = $this->faker->email;

        $formData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
        ];

        $object = new Contact();
        $object->setFirstName($firstName);
        $object->setLastName($lastName);
        $object->setEmail($email);

        $this->assertFormData(ContactType::class, $formData, $object);
    }

    /**
     * @return PreloadedExtension[]
     */
    #[Override]
    protected function getExtensions(): array
    {
        $fieldRepo = M::mock(CustomFieldRepository::class);
        $fieldRepo->shouldReceive('findByTargetOrdered')
            ->with(M::type(CustomFieldTarget::class))
            ->andReturn([]);

        $valueRepo = M::mock(CustomFieldValueRepository::class);
        $em = M::mock(EntityManagerInterface::class);
        $em->shouldReceive('contains')->zeroOrMoreTimes()->andReturn(false);

        $featureGate = $this->createStub(FeatureGate::class);
        $featureGate->method('isEnabled')->willReturn(true);

        return [
            new PreloadedExtension([
                new ContactType($featureGate),
                new CustomFieldValueCollectionType($fieldRepo, $valueRepo, new CustomFieldTypeResolver(), $em),
            ], []),
        ];
    }
}
