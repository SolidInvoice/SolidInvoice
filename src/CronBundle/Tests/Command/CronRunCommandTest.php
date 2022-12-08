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

namespace SolidInvoice\CronBundle\Tests\Command;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/** @covers \SolidInvoice\CronBundle\Command\CronRunCommand */
final class CronRunCommandTest extends TestCase
{
    use EnsureApplicationInstalled;

    public function testCronRunCommand(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $doctrine = $container->get('doctrine');
        $em = $doctrine->getManager();

        $entity = new RecurringInvoice();
        $entity
            ->setDateStart(new DateTimeImmutable())
            ->setFrequency('* * * * *')
            ->setStatus('active')
        ;

        $em->persist($entity);
        $em->flush();

        self::assertCount(0, $doctrine->getRepository(Invoice::class)->findAll());

        $application = new Application($kernel);

        $command = $application->find('cron:run');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running 1 due task. (1 total tasks)', $output);
        $this->assertStringContainsString('Running MessageTask: Create recurring invoice (1)', $output);
        $this->assertStringContainsString('[OK] 1/1 tasks ran, 1 succeeded', $output);

        self::assertCount(1, $doctrine->getRepository(Invoice::class)->findAll());
    }
}
