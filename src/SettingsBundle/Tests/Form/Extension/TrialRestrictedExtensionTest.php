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

namespace SolidInvoice\SettingsBundle\Tests\Form\Extension;

use PHPUnit\Framework\TestCase;
use SolidInvoice\SettingsBundle\Form\Extension\TrialRestrictedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @covers \SolidInvoice\SettingsBundle\Form\Extension\TrialRestrictedExtension
 */
final class TrialRestrictedExtensionTest extends TestCase
{
    private TrialRestrictedExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new TrialRestrictedExtension();
    }

    public function testBuildViewDisablesFieldWhenBothOptionsAreTrue(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => true,
            'subscription_in_trial' => true,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertTrue($view->vars['disabled']);
        self::assertTrue($view->vars['trial_restricted_active']);
        self::assertStringContainsString('trial-restricted', $view->vars['attr']['class'] ?? '');
    }

    public function testBuildViewForcesCheckboxUncheckedDuringTrial(): void
    {
        $view = new FormView();
        $view->vars['checked'] = true; // Simulate checkbox that was checked

        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => true,
            'subscription_in_trial' => true,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertFalse($view->vars['checked']);
        self::assertTrue($view->vars['disabled']);
        self::assertTrue($view->vars['trial_restricted_active']);
    }

    public function testBuildViewAddsTrialRestrictedCssClass(): void
    {
        $view = new FormView();
        $view->vars['attr']['class'] = 'existing-class';

        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => true,
            'subscription_in_trial' => true,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertStringContainsString('existing-class', $view->vars['attr']['class']);
        self::assertStringContainsString('trial-restricted', $view->vars['attr']['class']);
    }

    public function testBuildViewDoesNotDisableWhenTrialRestrictedIsFalse(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => false,
            'subscription_in_trial' => true,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertArrayNotHasKey('disabled', $view->vars);
        self::assertFalse($view->vars['trial_restricted_active']);
    }

    public function testBuildViewDoesNotDisableWhenSubscriptionNotInTrial(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => true,
            'subscription_in_trial' => false,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertArrayNotHasKey('disabled', $view->vars);
        self::assertFalse($view->vars['trial_restricted_active']);
    }

    public function testBuildViewDoesNotDisableWhenBothOptionsAreFalse(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => false,
            'subscription_in_trial' => false,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertArrayNotHasKey('disabled', $view->vars);
        self::assertFalse($view->vars['trial_restricted_active']);
    }

    public function testBuildViewHandlesEmptyAttrClass(): void
    {
        $view = new FormView();
        // No attr['class'] set initially

        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => true,
            'subscription_in_trial' => true,
        ];

        $this->extension->buildView($view, $form, $options);

        self::assertStringContainsString('trial-restricted', $view->vars['attr']['class']);
    }

    public function testConfigureOptionsSetCorrectDefaults(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $resolved = $resolver->resolve([]);

        self::assertFalse($resolved['trial_restricted']);
        self::assertFalse($resolved['subscription_in_trial']);
    }

    public function testConfigureOptionsAcceptsBooleanValues(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $resolved = $resolver->resolve([
            'trial_restricted' => true,
            'subscription_in_trial' => true,
        ]);

        self::assertTrue($resolved['trial_restricted']);
        self::assertTrue($resolved['subscription_in_trial']);
    }

    public function testConfigureOptionsRejectsInvalidTypes(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $this->expectException(\Symfony\Component\OptionsResolver\Exception\InvalidOptionsException::class);

        $resolver->resolve([
            'trial_restricted' => 'invalid',
        ]);
    }

    public function testGetExtendedTypesReturnsFormType(): void
    {
        $types = iterator_to_array($this->extension::getExtendedTypes());

        self::assertCount(1, $types);
        self::assertSame(FormType::class, $types[0]);
    }

    public function testBuildViewPreservesExistingDisabledState(): void
    {
        $view = new FormView();
        $view->vars['disabled'] = true; // Already disabled

        $form = $this->createMock(FormInterface::class);
        $options = [
            'trial_restricted' => false,
            'subscription_in_trial' => false,
        ];

        $this->extension->buildView($view, $form, $options);

        // Should remain disabled even though trial restrictions don't apply
        self::assertTrue($view->vars['disabled']);
    }
}
