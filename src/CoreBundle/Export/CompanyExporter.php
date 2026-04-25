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

namespace SolidInvoice\CoreBundle\Export;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Discovery\EntityDiscovery;
use SolidInvoice\CoreBundle\Export\Discovery\EntityExportSpec;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Serializer\ExportSerializer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use ZipArchive;
use function array_map;
use function sys_get_temp_dir;
use function uniqid;

/**
 * Produces a ZIP archive containing one file per company-owned entity plus a manifest.
 *
 * Expected to run inside a company-scoped context (CompanySelector::switchCompany()
 * has been called) so the CompanyFilter transparently limits queries to the export's
 * company.
 *
 * TODO(streaming): the current implementation materializes each entity's full result
 *   set before encoding. Switch to Doctrine's toIterable() + chunked writes and a
 *   streaming JSON/XML writer (e.g. XMLWriter) when we start seeing large tenants.
 * TODO(binary-attachments): include PDF invoices, uploaded receipts, and company
 *   logos under a `files/` subdirectory in the archive.
 */
final class CompanyExporter
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly EntityDiscovery $discovery,
        private readonly ExportSerializer $serializer,
        private readonly ManifestGenerator $manifestGenerator,
        private readonly Filesystem $filesystem,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Returns the archive's path relative to the project root.
     */
    public function export(ExportJob $job): string
    {
        $manager = $this->registry->getManager();
        assert($manager instanceof EntityManagerInterface);

        $specs = $this->discovery->discover();
        $format = $job->getFormat();

        $stagingDir = sys_get_temp_dir() . '/solidinvoice_export_' . $job->getId()->toBase58() . '_' . uniqid();
        $this->filesystem->mkdir($stagingDir, 0o755);

        try {
            $counts = $this->writeEntityFiles($manager, $specs, $format, $stagingDir);

            $manifest = $this->manifestGenerator->generate($job, $counts);
            $this->filesystem->dumpFile(
                $stagingDir . '/manifest.json',
                (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );

            $relativePath = $this->archivePath($job);
            $absolutePath = $this->projectDir . '/' . $relativePath;
            $this->filesystem->mkdir(dirname($absolutePath), 0o755);

            $this->zipDirectory($stagingDir, $absolutePath);

            return $relativePath;
        } finally {
            $this->filesystem->remove($stagingDir);
        }
    }

    /**
     * @param list<EntityExportSpec> $specs
     * @return array<string, int>
     */
    private function writeEntityFiles(
        EntityManagerInterface $manager,
        array $specs,
        ExportFormat $format,
        string $stagingDir,
    ): array {
        $counts = [];

        foreach ($specs as $spec) {
            $repository = $manager->getRepository($spec->entityClass);
            $entities = $repository->findAll();

            $normalized = array_map(
                fn (object $entity): mixed => $this->serializer->normalize(
                    $entity,
                    $format,
                    $this->normalizationContext($spec),
                ),
                $entities,
            );

            $payload = $this->serializer->encode($normalized, $format, $format->encoderContext($spec->filename));

            $filename = $stagingDir . '/' . $spec->filename . '.' . $format->extension();
            $this->filesystem->dumpFile($filename, $payload);

            $counts[$spec->filename] = count($entities);
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizationContext(EntityExportSpec $spec): array
    {
        return [
            AbstractObjectNormalizer::ATTRIBUTES => $spec->includedProperties,
            AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
            AbstractObjectNormalizer::SKIP_UNINITIALIZED_VALUES => true,
            AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
        ];
    }

    private function archivePath(ExportJob $job): string
    {
        return sprintf(
            'var/exports/%s/%s.zip',
            $job->getCompany()->getId()->toBase58(),
            $job->getId()->toBase58(),
        );
    }

    private function zipDirectory(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new RuntimeException(sprintf(
                'Could not create archive at "%s" (ZipArchive error code %d).',
                $zipPath,
                $result,
            ));
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
    }
}
