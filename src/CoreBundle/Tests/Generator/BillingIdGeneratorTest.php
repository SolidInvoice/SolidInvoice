<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Tests\Generator;

use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @covers \SolidInvoice\CoreBundle\Generator\BillingIdGenerator
 */
final class BillingIdGeneratorTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function testGenerateWithDefaultStrategy(): void
    {
        $autoIncrementGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $randomNumberGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $timestampGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);

        $autoIncrementGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('10');

        $randomNumberGenerator->expects(self::never())
            ->method('generate');

        $timestampGenerator->expects(self::never())
            ->method('generate');

        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['invoice/id_generation/strategy', 'auto_increment'],
                ['invoice/id_generation/prefix', ''],
                ['invoice/id_generation/suffix', ''],
            ]);

        $generator = new BillingIdGenerator(
            new ServiceLocator(
                [
                    'auto_increment' => static fn () => $autoIncrementGenerator,
                    'random_number' => static fn () => $randomNumberGenerator,
                    'timestamp' => static fn () => $timestampGenerator,
                ],
            ),
            $systemConfig,
        );

        self::assertSame('10', $generator->generate(new Invoice()));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function testGenerateWithCustomStrategy(): void
    {
        $autoIncrementGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $randomNumberGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $timestampGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);

        $autoIncrementGenerator->expects(self::never())
            ->method('generate');

        $randomNumberGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('100');

        $timestampGenerator->expects(self::never())
            ->method('generate');

        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                ['invoice/id_generation/prefix', ''],
                ['invoice/id_generation/suffix', ''],
            ]);

        $generator = new BillingIdGenerator(
            new ServiceLocator(
                [
                    'auto_increment' => static fn () => $autoIncrementGenerator,
                    'random_number' => static fn () => $randomNumberGenerator,
                    'timestamp' => static fn () => $timestampGenerator,
                ],
            ),
            $systemConfig,
        );

        self::assertSame('100', $generator->generate(new Invoice(), [], 'random_number'));
    }

    public function testGenerateWithPrefixAndSuffix(): void
    {
        $autoIncrementGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $randomNumberGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);
        $timestampGenerator = $this->createMock(BillingIdGenerator\IdGeneratorInterface::class);

        $autoIncrementGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('10');

        $randomNumberGenerator->expects(self::never())
            ->method('generate');

        $timestampGenerator->expects(self::never())
            ->method('generate');

        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig->expects(self::exactly(3))
            ->method('get')
            ->willReturnMap([
                ['invoice/id_generation/strategy', 'auto_increment'],
                ['invoice/id_generation/prefix', 'INV-'],
                ['invoice/id_generation/suffix', '-00'],
            ]);

        $generator = new BillingIdGenerator(
            new ServiceLocator(
                [
                    'auto_increment' => static fn () => $autoIncrementGenerator,
                    'random_number' => static fn () => $randomNumberGenerator,
                    'timestamp' => static fn () => $timestampGenerator,
                ],
            ),
            $systemConfig,
        );

        self::assertSame('INV-10-00', $generator->generate(new Invoice()));
    }
}
