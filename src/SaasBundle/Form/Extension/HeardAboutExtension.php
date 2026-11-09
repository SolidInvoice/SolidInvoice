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

namespace SolidInvoice\SaasBundle\Form\Extension;

use SolidInvoice\SaasBundle\Analytics\DataLayerQueue;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Enum\UserSettingType;
use SolidInvoice\UserBundle\Onboarding\Form\Step\CompanySetupStep;
use SolidInvoice\UserBundle\Repository\UserSettingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Length;
use function array_combine;
use function array_map;
use function is_string;
use function trim;

/**
 * Adds the optional "How did you hear about SolidInvoice?" question to the
 * onboarding company step. The answer is stored as user settings and pushed
 * to the dataLayer as a `signup_survey` event (choice only, never free text).
 */
final class HeardAboutExtension extends AbstractTypeExtension
{
    final public const array CHOICES = ['google', 'ai_assistant', 'colleague', 'invoice_received', 'launch', 'review_site', 'github', 'other'];

    public function __construct(
        private readonly Security $security,
        private readonly UserSettingRepository $userSettingRepository,
        private readonly DataLayerQueue $dataLayerQueue,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('heardAbout', ChoiceType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'onboarding.company.fields.heard_about.label',
            'placeholder' => 'onboarding.company.fields.heard_about.placeholder',
            'choices' => array_combine(
                array_map(static fn (string $choice): string => 'onboarding.company.fields.heard_about.choices.' . $choice, self::CHOICES),
                self::CHOICES,
            ),
            'row_attr' => ['class' => 'mb-3 heard-about'],
        ]);

        $builder->add('heardAboutOther', TextType::class, [
            'mapped' => false,
            'required' => false,
            'label' => false,
            'constraints' => [new Length(max: 255)],
            'attr' => ['placeholder' => 'onboarding.company.fields.heard_about.other_placeholder'],
            'row_attr' => ['class' => 'mb-4 heard-about-other'],
        ]);

        // Below the validation listener (priority 0), so isValid() is meaningful.
        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->onPostSubmit(...), -10);
    }

    public static function getExtendedTypes(): iterable
    {
        yield CompanySetupStep::class;
    }

    private function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $user = $this->security->getUser();
        $heardAbout = $form->get('heardAbout')->getData();

        if (! $form->isValid() || ! $user instanceof User || ! is_string($heardAbout)) {
            return;
        }

        $this->userSettingRepository->saveSetting($user, UserSettingType::SignupHeardAbout, $heardAbout);

        $other = $form->get('heardAboutOther')->getData();
        if ($heardAbout === 'other' && is_string($other) && trim($other) !== '') {
            $this->userSettingRepository->saveSetting($user, UserSettingType::SignupHeardAboutOther, trim($other));
        }

        $this->dataLayerQueue->push(['event' => 'signup_survey', 'heard_about' => $heardAbout]);
    }
}
