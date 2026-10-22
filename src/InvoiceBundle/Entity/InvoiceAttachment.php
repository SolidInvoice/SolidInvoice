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

namespace SolidInvoice\InvoiceBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\InvoiceBundle\Api\InvoiceAttachmentDeleteProcessor;
use SolidInvoice\InvoiceBundle\Repository\InvoiceAttachmentRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Uid\Ulid;

/**
 * A file attached to an invoice. The blob itself lives behind
 * `SolidInvoice\CoreBundle\Storage\AttachmentStorageInterface`; this entity only
 * carries the metadata and the generated `storageKey` that locates it.
 * @see \SolidInvoice\InvoiceBundle\Tests\Entity\InvoiceAttachmentTest
 */
#[ORM\Table(name: InvoiceAttachment::TABLE_NAME)]
#[ORM\Index(columns: ['company_id'])]
#[ORM\Index(columns: ['invoice_id'])]
#[ORM\Entity(repositoryClass: InvoiceAttachmentRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/invoices/{invoiceId}/attachments',
            uriVariables: [
                'invoiceId' => new Link(
                    fromProperty: 'attachments',
                    fromClass: Invoice::class,
                ),
            ],
        ),
        new Get(),
        new Delete(processor: InvoiceAttachmentDeleteProcessor::class),
        new Patch(),
    ],
    normalizationContext: [
        'groups' => ['invoice_attachment_api:read'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
    denormalizationContext: [
        'groups' => ['invoice_attachment_api:write'],
        AbstractObjectNormalizer::SKIP_NULL_VALUES => false,
    ],
)]
class InvoiceAttachment
{
    final public const string TABLE_NAME = 'invoice_attachments';

    /**
     * Content-sniffed MIME types a file is accepted under. A code constant, not a
     * user-editable setting: letting a user add `image/svg+xml` or `text/html` would
     * re-open stored XSS, which is exactly why ImageUploadType::ALLOWED_MIME_TYPES
     * excludes SVG for the same reason. ZIP is excluded because an opaque container
     * defeats the point of an allowlist.
     */
    public const array ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'text/plain',
        'text/csv',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.text',
    ];

    use CompanyAware;
    use TimeStampable;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[ORM\Column(name: 'id', type: UlidType::NAME)]
    #[Groups(['invoice_attachment_api:read'])]
    private Ulid $id;

    /**
     * Bare ULID rather than a `ManyToOne` to `User`, for the same reason given at
     * `ExportJob::$requestedBy`: InvoiceBundle has no production dependency on
     * UserBundle today. Nullable, `ON DELETE SET NULL` — deleting a user must not
     * delete the invoice's evidence.
     */
    #[ORM\Column(name: 'uploaded_by', type: UlidType::NAME, nullable: true)]
    private ?Ulid $uploadedBy = null;

    #[ORM\Column(name: 'shared_with_client', type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['invoice_attachment_api:read', 'invoice_attachment_api:write'])]
    private bool $sharedWithClient = false;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Invoice::class, inversedBy: 'attachments')]
        #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
        private Invoice $invoice,
        #[ORM\Column(name: 'filename', type: Types::STRING, length: 255)]
        #[Groups(['invoice_attachment_api:read'])]
        private string $filename,
        #[ORM\Column(name: 'mime_type', type: Types::STRING, length: 127)]
        #[Groups(['invoice_attachment_api:read'])]
        private string $mimeType,
        #[ORM\Column(name: 'file_size', type: Types::INTEGER)]
        #[Groups(['invoice_attachment_api:read'])]
        private int $fileSize, /**
     * Generated from ULIDs, never derived from user input, and never serialized —
     * it must not leave the server.
     */
        #[ORM\Column(name: 'storage_key', type: Types::STRING, length: 512, unique: true)]
        private string $storageKey
    ) {
        $this->id = new Ulid();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getInvoice(): Invoice
    {
        return $this->invoice;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function getUploadedBy(): ?Ulid
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(?Ulid $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;

        return $this;
    }

    public function isSharedWithClient(): bool
    {
        return $this->sharedWithClient;
    }

    public function setSharedWithClient(bool $sharedWithClient): self
    {
        $this->sharedWithClient = $sharedWithClient;

        return $this;
    }

    /**
     * Re-exposes the `created` column under the `invoice_attachment_api:read` group.
     * `TimeStampable::$created` carries `#[Ignore]`, which the serializer keys by
     * property name — a `#[Groups]` on `getCreated()` itself would merge into that
     * same ignored attribute and never be emitted. A distinctly-named accessor with
     * `#[SerializedName('created')]` is the same workaround Invoice::getCreatedTimestamp()
     * and Quote::getCreatedTimestamp() use for the `searchable` group, including the
     * Unix-timestamp return type so this entity serializes `created` the same way as
     * every other API resource in an invoice response.
     */
    #[Groups(['invoice_attachment_api:read'])]
    #[SerializedName('created')]
    public function getCreatedTimestamp(): ?int
    {
        return $this->getCreated()?->getTimestamp();
    }
}
