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

namespace SolidInvoice\NotificationBundle\Form\Type;

use Override;
use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use function strtolower;

/**
 * @extends AbstractType<TransportSetting>
 */
final class TransportSettingType extends AbstractType
{
    /**
     * @param ServiceLocator<ConfiguratorInterface> $transportConfigurations
     */
    public function __construct(
        #[AutowireLocator(services: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder->add('name');

        $builder->add('transport', HiddenType::class);

        $builder->addDependent('settings', 'transport', function (DependentField $field, ?string $setting): void {
            if (null === $setting) {
                return;
            }

            $field->add($this->transportConfigurations->get($setting)->getForm());
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => TransportSetting::class]);
        $resolver->setRequired('type');
        $resolver->setAllowedTypes('type', 'string');
        $resolver->setAllowedValues('type', ['texter', 'chatter']);

        $resolver->setDefault('validation_groups', static fn (FormInterface $form) => ['Default', strtolower((string) $form->get('transport')->getData())]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'notification_transport_setting';
    }
}
