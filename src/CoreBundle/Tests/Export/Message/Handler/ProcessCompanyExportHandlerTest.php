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

namespace SolidInvoice\CoreBundle\Tests\Export\Message\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\CompanyExporter;
use SolidInvoice\CoreBundle\Export\Discovery\EntityDiscovery;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Enum\ExportStatus;
use SolidInvoice\CoreBundle\Export\Message\Handler\ProcessCompanyExportHandler;
use SolidInvoice\CoreBundle\Export\Message\RequestCompanyExport;
use SolidInvoice\CoreBundle\Repository\ExportJobRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Mailer\Test\InteractsWithMailer;
use ZipArchive;

#[CoversClass(ProcessCompanyExportHandler::class)]
#[CoversClass(CompanyExporter::class)]
#[CoversClass(EntityDiscovery::class)]
final class ProcessCompanyExportHandlerTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;
    use InteractsWithMailer;

    public function testHandlerBuildsArchiveAndNotifiesUser(): void
    {
        $user = UserFactory::createOne([
            'email' => 'exporter@example.com',
            'companies' => [$this->company],
        ]);

        $repository = $this->exportJobRepository();
        $job = new ExportJob($user->getId(), ExportFormat::Json)->setCompany($this->company);
        $repository->save($job);

        $handler = self::getContainer()->get(ProcessCompanyExportHandler::class);
        $handler(new RequestCompanyExport($job->getId(), $this->company->getId(), $user->getId()));

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $reloaded = $repository->find($job->getId());
        self::assertInstanceOf(ExportJob::class, $reloaded);
        self::assertSame(ExportStatus::Completed, $reloaded->getStatus());
        self::assertNotNull($reloaded->getArchivePath());

        $absolutePath = $reloaded->resolveAbsolutePath(self::getContainer()->getParameter('kernel.project_dir'));
        self::assertNotNull($absolutePath);
        self::assertFileExists($absolutePath);

        $this->assertArchiveContainsManifest($absolutePath);

        $this->mailer()->sentEmails()->assertCount(1);
        $this->mailer()->sentEmails()->first()->assertTo('exporter@example.com');

        new Filesystem()->remove($absolutePath);
    }

    public function testSkipsWhenJobIsNotPending(): void
    {
        $user = UserFactory::createOne([
            'email' => 'exporter-idempotent@example.com',
            'companies' => [$this->company],
        ]);

        $repository = $this->exportJobRepository();
        $job = new ExportJob($user->getId(), ExportFormat::Json)->setCompany($this->company);
        $job->markFailed('pre-existing failure');

        $repository->save($job);

        $handler = self::getContainer()->get(ProcessCompanyExportHandler::class);
        $handler(new RequestCompanyExport($job->getId(), $this->company->getId(), $user->getId()));

        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        $reloaded = $repository->find($job->getId());
        self::assertInstanceOf(ExportJob::class, $reloaded);
        self::assertSame(ExportStatus::Failed, $reloaded->getStatus());
        self::assertSame('pre-existing failure', $reloaded->getFailureReason());
    }

    private function exportJobRepository(): ExportJobRepository
    {
        /** @var ExportJobRepository $repository */
        $repository = self::getContainer()->get(ExportJobRepository::class);
        return $repository;
    }

    private function assertArchiveContainsManifest(string $archivePath): void
    {
        $zip = new ZipArchive();
        self::assertTrue($zip->open($archivePath));

        $manifestContents = $zip->getFromName('manifest.json');
        $zip->close();

        self::assertNotFalse($manifestContents);

        $manifest = json_decode($manifestContents, true, 8, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        self::assertArrayHasKey('solidinvoice_version', $manifest);
        self::assertArrayHasKey('entity_counts', $manifest);
    }
}
