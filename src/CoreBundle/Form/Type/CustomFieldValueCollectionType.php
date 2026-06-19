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
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Error;
use Override;
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

/**
 * @see \SolidInvoice\CoreBundle\Tests\Form\Type\CustomFieldValueCollectionTypeTest
 * @extends AbstractType<mixed>
 */
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
        $existingId = $options['existing_target_id'] ?? null;
        if ($defs !== [] && $existingId instanceof Ulid) {
            foreach ($this->values->findForRecord($target, $existingId) as $v) {
                $existingValues[(string) $v->getField()->getId()] = $v;
            }
        } elseif ($defs !== [] && $parent !== null && method_exists($parent, 'getId') && $parent->getId() instanceof Ulid) {
            foreach ($this->values->findForRecord($target, $parent->getId()) as $v) {
                $existingValues[(string) $v->getField()->getId()] = $v;
            }
        }

        $isNewParent = $parent === null || ! (method_exists($parent, 'getId') && $parent->getId() instanceof Ulid && $this->em->contains($parent));
        if ($existingId instanceof Ulid) {
            $isNewParent = false;
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
            } elseif ($isNewParent && $def->getDefaultValue() !== null) {
                $opts['data'] = $this->resolver->deserialize($def, $def->getDefaultValue());
            }

            $builder->add($def->getFieldKey(), $type, $opts);
        }

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event) use ($defs, $target, $existingValues): void {
            $form = $event->getForm();
            if ($form->getConfig()->getOption('manage_persistence') === false) {
                return;
            }

            $parent = $form->getConfig()->getOption('parent_record');
            if ($parent === null || ! method_exists($parent, 'getId')) {
                return;
            }

            $parentId = $parent->getId();
            $parentCompany = null;
            if (method_exists($parent, 'getCompany')) {
                try {
                    $parentCompany = $parent->getCompany();
                } catch (Error) {
                    // Company not yet initialized on the parent (new record).
                    // CompanyListener (prePersist) will assign one when CustomFieldValue is persisted.
                }
            }

            // An entity is "persisted" (has a stable Doctrine-assigned ID) only when it
            // is already managed by the UnitOfWork. For new (unpersisted) entities,
            // Doctrine's UlidGenerator overwrites any constructor-set ID on first persist,
            // so we must defer targetId assignment until after postPersist fires.
            $parentIsManaged = $parentId instanceof Ulid && $this->em->contains($parent);

            /** @var list<CustomFieldValue> $pendingValues */
            $pendingValues = [];

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
                    $value = new CustomFieldValue()
                        ->setField($def)
                        ->setTarget($target)
                        ->setValue($serialized);
                    if ($parentCompany !== null) {
                        $value->setCompany($parentCompany);
                    }

                    if ($parentIsManaged) {
                        $value->setTargetId($parentId);
                        $this->em->persist($value);
                    } else {
                        // Parent has no stable Doctrine ID yet (new record). Defer
                        // target-ID assignment to postPersist so it runs after Doctrine
                        // assigns the real ULID.
                        $pendingValues[] = $value;
                    }
                } else {
                    $existing->setValue($serialized);
                }
            }

            if ($pendingValues !== []) {
                $em = $this->em;
                $listenerObj = new class($parent, $pendingValues, $em) {
                    /**
                     * @var list<CustomFieldValue>
                     */
                    private array $toFlush = [];

                    private ?object $self = null;

                    /**
                     * @param list<CustomFieldValue> $pending
                     */
                    public function __construct(
                        private readonly object $parent,
                        private readonly array $pending,
                        private readonly EntityManagerInterface $em,
                    ) {
                    }

                    public function setSelf(object $self): void
                    {
                        $this->self = $self;
                    }

                    public function postPersist(PostPersistEventArgs $args): void
                    {
                        if ($args->getObject() !== $this->parent) {
                            return;
                        }

                        $id = $this->parent->getId();
                        if (! $id instanceof Ulid) {
                            return;
                        }

                        foreach ($this->pending as $value) {
                            $value->setTargetId($id);
                            $this->em->persist($value);
                            $this->toFlush[] = $value;
                        }
                    }

                    public function postFlush(PostFlushEventArgs $args): void
                    {
                        if ($this->toFlush === []) {
                            return;
                        }

                        $this->toFlush = [];

                        // Unregister before flushing to prevent re-entry.
                        if ($this->self !== null) {
                            $this->em->getEventManager()->removeEventListener(['postPersist', 'postFlush'], $this->self);
                        }

                        $this->em->flush();
                    }
                };
                $listenerObj->setSelf($listenerObj);
                $this->em->getEventManager()->addEventListener(['postPersist', 'postFlush'], $listenerObj);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['target']);
        $resolver->setAllowedTypes('target', CustomFieldTarget::class);
        $resolver->setDefined('parent_record');
        $resolver->setAllowedTypes('parent_record', ['object', 'null']);
        $resolver->setDefined('existing_target_id');
        $resolver->setAllowedTypes('existing_target_id', [Ulid::class, 'null']);
        $resolver->setDefined('manage_persistence');
        $resolver->setAllowedTypes('manage_persistence', 'bool');
        $resolver->setDefaults([
            'mapped' => false,
            'label' => false,
            'parent_record' => null,
            'existing_target_id' => null,
            'manage_persistence' => true,
        ]);
    }

    #[Override]
    public function getBlockPrefix(): string
    {
        return 'custom_field_values';
    }
}
