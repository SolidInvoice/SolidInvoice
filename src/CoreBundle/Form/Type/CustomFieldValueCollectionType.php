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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldTypeResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Uid\Ulid;

final class CustomFieldValueCollectionType extends AbstractType
{
    public function __construct(
        private readonly CustomFieldRepository $fields,
        private readonly CustomFieldValueRepository $values,
        private readonly CustomFieldTypeResolver $resolver,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $target = $options['target'];
        \assert($target instanceof CustomFieldTarget);

        $defs = $this->fields->findByTargetOrdered($target);
        // Stash defs so the post-submit handler doesn't re-query.
        $builder->setAttribute('custom_field_defs', $defs);

        $existingValues = [];
        $parent = $options['parent_record'] ?? null;
        if ($parent !== null && method_exists($parent, 'getId') && $parent->getId() instanceof Ulid) {
            foreach ($this->values->findForRecord($target, $parent->getId()) as $v) {
                $existingValues[(string) $v->getField()->getId()] = $v;
            }
        }

        foreach ($defs as $def) {
            [$type, $opts] = $this->resolver->formTypeAndOptions($def);
            $opts['label'] = $def->getLabel();
            $opts['required'] = $def->isRequired();
            $opts['mapped'] = false;
            $opts['constraints'] = $this->resolver->constraints($def);

            $existing = $existingValues[(string) $def->getId()] ?? null;
            if ($existing !== null) {
                $opts['data'] = $this->resolver->deserialize($def, $existing->getValue());
            }

            $builder->add($def->getFieldKey(), $type, $opts);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) use ($defs, $target, $existingValues): void {
            $form = $event->getForm();
            $parent = $form->getConfig()->getOption('parent_record');
            if ($parent === null || ! method_exists($parent, 'getId') || ! $parent->getId() instanceof Ulid) {
                return;
            }

            $companyId = method_exists($parent, 'getCompany') ? $parent->getCompany() : null;

            foreach ($defs as $def) {
                $child = $form->get($def->getFieldKey());
                $serialized = $this->resolver->serialize($def, $child->getData());
                $existing = $existingValues[(string) $def->getId()] ?? null;

                if ($serialized === null) {
                    if ($existing !== null) {
                        $this->em->remove($existing);
                    }

                    continue;
                }

                if ($existing === null) {
                    $value = (new CustomFieldValue())
                        ->setField($def)
                        ->setTarget($target)
                        ->setTargetId($parent->getId())
                        ->setValue($serialized);
                    if ($companyId !== null) {
                        $value->setCompany($companyId);
                    }

                    $this->em->persist($value);
                } else {
                    $existing->setValue($serialized);
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['target', 'parent_record']);
        $resolver->setAllowedTypes('target', CustomFieldTarget::class);
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'custom_field_values';
    }
}
