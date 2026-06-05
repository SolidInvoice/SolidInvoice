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

namespace SolidInvoice\SettingsBundle\Tests\Twig\Components;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use SolidInvoice\SettingsBundle\Twig\Components\Settings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

// Exercises preMount() and instantiateForm() directly; avoids full template render (iconify network).
#[CoversClass(Settings::class)]
final class SettingsEmptyStateTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private Settings $component;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockObject&SettingsRepository $repo */
        $repo = $this->createMock(SettingsRepository::class);
        $repo->method('findAll')->willReturn([]);
        $emptyRepo = $repo;

        /** @var PropertyAccessorInterface $propertyAccessor */
        $propertyAccessor = self::getContainer()->get('property_accessor');

        $this->component = new Settings($emptyRepo, $propertyAccessor);
        $this->component->setContainer(self::getContainer());
    }

    public function testPreMountDoesNotThrowWhenSettingsEmpty(): void
    {
        $this->component->preMount();

        self::assertSame('', $this->component->section);
    }

    public function testInstantiateFormDoesNotThrowWhenSettingsEmpty(): void
    {
        $this->component->preMount();

        $method = new ReflectionMethod(Settings::class, 'instantiateForm');
        $form = $method->invoke($this->component);

        self::assertInstanceOf(FormInterface::class, $form);
    }

    public function testInstantiateFormWithGarbageSectionAndEmptySettings(): void
    {
        $this->component->preMount();
        // Simulates a garbage ?section= URL param applied after preMount
        $this->component->section = '\'"""""""';

        $method = new ReflectionMethod(Settings::class, 'instantiateForm');
        $form = $method->invoke($this->component);

        self::assertInstanceOf(FormInterface::class, $form);
    }
}
