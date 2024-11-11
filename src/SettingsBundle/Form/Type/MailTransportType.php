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

namespace SolidInvoice\SettingsBundle\Form\Type;

use const JSON_THROW_ON_ERROR;
use JsonException;
use SolidInvoice\MailerBundle\Configurator\ConfiguratorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;
use Traversable;
use function array_combine;
use function array_map;
use function is_array;
use function is_string;
use function iterator_to_array;
use function json_validate;
use function str_replace;
use function strtolower;

final class MailTransportType extends AbstractType
{
    /**
     * @param list<ConfiguratorInterface>|Traversable<ConfiguratorInterface> $transports
     */
    public function __construct(
        private readonly iterable $transports,
        private readonly StimulusHelper $stimulusHelper
    ) {
    }

    /**
     * @throws JsonException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transports = is_array($this->transports) ? $this->transports : iterator_to_array($this->transports);

        $choices = array_map(static fn (ConfiguratorInterface $configurator) => $configurator->getName(), $transports);

        $builder->add(
            'provider',
            ChoiceType::class,
            [
                'choices' => array_combine($choices, $choices),
                'placeholder' => 'Choose Mail Provider',
                'label' => 'Mail Provider',
                'attr' => [
                    'data-mailsettings-target' => 'provider',
                ],
            ]
        );

        $currentProvider = null;

        if (isset($options['data']) && is_string($options['data']) && json_validate($options['data'])) {
            $mailSettings = json_decode($options['data'], true, JSON_THROW_ON_ERROR, JSON_THROW_ON_ERROR);
            $currentProvider = $mailSettings['provider'];
        }

        foreach ($transports as $transport) {
            $sanitizedName = str_replace(' ', '-', $transport->getName());
            $builder->add(
                $sanitizedName,
                $transport->getForm(),
                [
                    'row_attr' =>
                        [
                            'class' => 'mb-0 provider-' . $sanitizedName . ($currentProvider === $transport->getName() ? '' : ' d-none'),
                            'data-provider' => $sanitizedName,
                        ],
                ]
            );
        }

        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            /**
             * @return null|array<string, mixed>
             */
            public function transform(mixed $value): ?array
            {
                if (! is_string($value)) {
                    return null;
                }

                $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                return [
                    'provider' => $data['provider'] ?? null,
                    str_replace(' ', '-', (string) $data['provider']) => $data['config'] ?? [],
                ];
            }

            public function reverseTransform(mixed $value): ?string
            {
                if (null === $value) {
                    return null;
                }

                $provider = $value['provider'] ?? null;

                if (null === $provider) {
                    return null;
                }

                return json_encode(['provider' => $value['provider'], 'config' => $value[str_replace(' ', '-', (string) $provider)]], JSON_THROW_ON_ERROR);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $stimulusAttributes = $this->stimulusHelper->createStimulusAttributes();
        $stimulusAttributes->addController('mailsettings');

        $resolver->setDefaults([
            'validation_groups' => static fn (FormInterface $form) => ['Default', strtolower(str_replace(' ', '_', $form->get('provider')->getData() ?? ''))],
            'attr' => $stimulusAttributes->toArray(),
        ]);
    }
}
