<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Form\Type;

use SolidInvoice\NotificationBundle\Configurator\ConfiguratorInterface;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;
use function strtolower;

final class TransportSettingType extends AbstractType
{
    public function __construct(
        #[TaggedLocator(tag: ConfiguratorInterface::DI_TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $transportConfigurations
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transports = [];

        foreach ($this->transportConfigurations->getProvidedServices() as $serviceId => $class) {
            $transports[$class::getType()][$serviceId] = $serviceId;
        }

        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('name')
            ->add('transport', ChoiceType::class, [
                'choices' => $transports[$options['type']],
                'placeholder' => 'Integration',
                'label' => 'Integration',
                'autocomplete' => true,
            ])->addDependent('settings', 'transport', function (DependentField $field, ?string $setting): void {
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

        $resolver->setDefault('validation_groups', static function (FormInterface $form) {
            return ['Default', strtolower($form->get('transport')->getData())];
        });
    }

    public function getBlockPrefix(): string
    {
        return 'notification_transport_setting';
    }
}
