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

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\SettingsBundle\Form\Type\TemplateEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class BillingTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 100]),
                ],
            ])
            ->add('content', TemplateEditorType::class, [
                'label' => 'Template',
                'required' => true,
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $template = $event->getData();

            if (! $template instanceof BillingTemplate) {
                return;
            }

            if ($template->isSystem() && '' !== $template->getId()?->toRfc4122()) {
                $event->getForm()->add('name', TextType::class, [
                    'label' => 'Name',
                    'disabled' => true,
                ]);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BillingTemplate::class,
        ]);
    }
}
