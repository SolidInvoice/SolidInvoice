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
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\ExportJob;
use SolidInvoice\CoreBundle\Export\Discovery\EntityDiscovery;
use SolidInvoice\CoreBundle\Export\Discovery\EntityExportSpec;
use SolidInvoice\CoreBundle\Export\Enum\ExportFormat;
use SolidInvoice\CoreBundle\Export\Serializer\ExportSerializer;
use SplFileInfo;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Uid\Ulid;
use ZipArchive;
use function array_map;
use function bin2hex;
use function random_bytes;
use function sys_get_temp_dir;

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
final readonly class CompanyExporter
{
    public function __construct(
        private ManagerRegistry $registry,
        private EntityDiscovery $discovery,
        private EntityRowNormalizer $rowNormalizer,
        private ExportSerializer $serializer,
        private ManifestGenerator $manifestGenerator,
        private Filesystem $filesystem,
        private CompanySelector $companySelector,
        private string $projectDir,
    ) {
    }

    /**
     * Returns the archive's path relative to the project root.
     */
    public function export(ExportJob $job): string
    {
        // Defense in depth: the handler always switches company before invoking the
        // exporter, but explicitly asserting it here means any future caller that
        // forgets to switch (tests, console commands) fails loudly rather than
        // silently exporting another tenant's rows for child entities that fall
        // back to repository->findAll().
        if (! $this->companySelector->getCompany() instanceof Ulid) {
            throw new RuntimeException('CompanyExporter requires an active company context (CompanySelector::switchCompany).');
        }

        $manager = $this->registry->getManager();
        assert($manager instanceof EntityManagerInterface);

        $specs = $this->discovery->discover();
        $format = $job->getFormat();

        $stagingDir = sys_get_temp_dir()
            . '/solidinvoice_export_'
            . $job->getId()->toBase58()
            . '_'
            . bin2hex(random_bytes(8));
        $this->filesystem->mkdir($stagingDir, 0o755);

        try {
            $counts = $this->writeEntityFiles($manager, $specs, $format, $stagingDir);

            $manifest = $this->manifestGenerator->generate($job, $counts);
            // JSON_THROW_ON_ERROR surfaces encoder failures (e.g. a non-UTF-8 byte
            // sneaking in via an entity field) rather than silently writing an empty
            // manifest from a `false`-cast result.
            $this->filesystem->dumpFile(
                $stagingDir . '/manifest.json',
                json_encode(
                    $manifest,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
                ),
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
            $entities = $this->fetchEntities($manager, $spec);
            $metadata = $manager->getClassMetadata($spec->entityClass);

            $rows = array_map(
                fn (object $entity): array => $this->rowNormalizer->normalize($entity, $metadata, $spec),
                $entities,
            );

            $payload = $this->serializer->encode($rows, $format, $format->encoderContext($spec->filename));

            $filename = $stagingDir . '/' . $spec->filename . '.' . $format->extension();
            $this->filesystem->dumpFile($filename, $payload);

            $counts[$spec->filename] = count($entities);
        }

        return $counts;
    }

    /**
     * Fetches the entity rows to export.
     *
     * For CompanyAware roots, we rely on the active CompanyFilter to scope rows.
     * For child entities (no `company_id`), we explicitly join through the spec's
     * `companyScopeAssociation` so a child's parent must belong to the active
     * company — this prevents cross-tenant leakage.
     *
     * @return list<object>
     */
    private function fetchEntities(EntityManagerInterface $manager, EntityExportSpec $spec): array
    {
        if ($spec->companyScopeAssociation === null) {
            /** @var list<object> $result */
            $result = $manager->getRepository($spec->entityClass)->findAll();

            return $result;
        }

        $activeCompanyId = $this->companySelector->getCompany();
        if (! $activeCompanyId instanceof Ulid) {
            return [];
        }

        /** @var list<object> $result */
        $result = $manager->getRepository($spec->entityClass)
            ->createQueryBuilder('e')
            ->innerJoin('e.' . $spec->companyScopeAssociation, 'p')
            ->andWhere('p.company = :company')
            ->setParameter('company', $activeCompanyId, UlidType::NAME)
            ->getQuery()
            ->getResult();

        return $result;
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
            /** @var SplFileInfo $file */
            $relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
            $zip->addFile($file->getPathname(), $relativePath);
        }

        $zip->close();
    }
}
