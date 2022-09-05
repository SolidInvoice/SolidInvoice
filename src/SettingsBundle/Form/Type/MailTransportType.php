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

use SolidInvoice\CoreBundle\Form\Type\Select2Type;
use SolidInvoice\MailerBundle\Configurator\ConfiguratorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;

final class MailTransportType extends AbstractType
{
    /**
     * @var iterable|ConfiguratorInterface[]|Traversable
     */
    private $transports;

    public function __construct(iterable $transports)
    {
        $this->transports = $transports;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transports = \is_array($this->transports) ? $this->transports : \iterator_to_array($this->transports);

        $choices = \array_map(static function (ConfiguratorInterface $configurator) {
            return $configurator->getName();
        }, $transports);

        $builder->add(
            'provider',
            Select2Type::class,
            [
            'choices' => \array_combine($choices, $choices),
            'placeholder' => 'Choose Mail Provider',
            'label' => 'Mail Provider',
        ]
        );

        foreach ($transports as $transport) {
            $builder->add(\str_replace(' ', '-', $transport->getName()), $transport->getForm(), ['attr' => ['class' => 'd-none']]);
        }

        $builder->addModelTransformer(new class() implements DataTransformerInterface {
            /**
             * @return array<string, mixed>
             */
            public function transform($value): ?array
            {
                if (! is_string($value)) {
                    return null;
                }

                $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

                return [
                    'provider' => $data['provider'] ?? null,
                    \str_replace(' ', '-', $data['provider']) => $data['config'] ?? [],
                ];
            }

            public function reverseTransform($value): ?string
            {
                if (null === $value) {
                    return null;
                }

                $provider = $value['provider'] ?? null;

                if (null === $provider) {
                    return null;
                }

                return json_encode(['provider' => $value['provider'], 'config' => $value[\str_replace(' ', '-', $provider)]], JSON_THROW_ON_ERROR);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => static function (FormInterface $form) {
                return ['Default', \strtolower(\str_replace(' ', '_', $form->get('provider')->getData()))];
            },
        ]);
    }
}
