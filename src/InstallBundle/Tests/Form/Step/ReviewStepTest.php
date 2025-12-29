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

namespace SolidInvoice\InstallBundle\Tests\Form\Step;

use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Form\Step\ReviewStep;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @covers \SolidInvoice\InstallBundle\Form\Step\ReviewStep
 */
final class ReviewStepTest extends TestCase
{
    public function testBuildForm(): void
    {
        $csrfToken = new CsrfToken('system_installation', 'test_token_value');

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects(self::once())
            ->method('getToken')
            ->with('system_installation')
            ->willReturn($csrfToken);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->expects(self::once())
            ->method('add')
            ->with(
                'token',
                self::anything(),
                self::callback(fn (array $options): bool => $options['data'] === 'test_token_value')
            )
            ->willReturnSelf();

        $reviewStep = new ReviewStep($csrfTokenManager);
        $reviewStep->buildForm($builder, []);
    }

    public function testConfigureOptions(): void
    {
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $reviewStep = new ReviewStep($csrfTokenManager);

        $resolver = new OptionsResolver();
        $reviewStep->configureOptions($resolver);

        $options = $resolver->resolve();

        self::assertSame(Installation::class, $options['data_class']);
        self::assertTrue($options['inherit_data']);
    }
}
