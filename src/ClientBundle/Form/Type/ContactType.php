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

namespace SolidInvoice\ClientBundle\Form\Type;

use Override;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Form\Type\CustomFieldValueCollectionType;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see \SolidInvoice\ClientBundle\Tests\Form\Type\ContactTypeTest
 * @extends AbstractType<Contact>
 */
class ContactType extends AbstractType
{
    public function __construct(
        private readonly FeatureGate $featureGate,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('firstName', null, ['sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('lastName', null, ['sanitize_html' => true, 'allow_single_quotes' => true]);
        $builder->add('email');

        if ($this->featureGate->isEnabled(Feature::CustomFields->value)) {
            $builder->add('customFields', CustomFieldValueCollectionType::class, [
                'target' => CustomFieldTarget::CONTACT,
                'parent_record' => $options['data'] ?? null,
                'manage_persistence' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', Contact::class);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'contact';
    }
}
