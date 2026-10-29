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

namespace SolidInvoice\SaasBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function mb_substr;

/**
 * A client email from a new account that was not delivered, waiting for the operator to release
 * or reject it. The message is kept fully rendered (MIME, attachments included), so releasing it
 * sends exactly what was held.
 */
#[ORM\Table(name: HeldEmail::TABLE_NAME)]
#[ORM\Entity]
class HeldEmail
{
    final public const string TABLE_NAME = 'held_emails';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255)]
    private string $subject;

    /**
     * @param list<string> $recipients
     * @param array<string, int> $reasons signal => points
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Company::class)]
        #[ORM\JoinColumn(name: 'company_id', nullable: false, onDelete: 'CASCADE')]
        private Company $company,
        #[ORM\Column(name: 'sender', type: Types::STRING, length: 255)]
        private string $sender,
        #[ORM\Column(name: 'recipients', type: Types::JSON)]
        private array $recipients,
        string $subject,
        // TEXT without a length is LONGTEXT on MySQL; an attached PDF does not fit in 64KB.
        #[ORM\Column(name: 'message', type: Types::TEXT)]
        private string $message,
        #[ORM\Column(name: 'reasons', type: Types::JSON)]
        private array $reasons,
        #[ORM\Column(name: 'created', type: Types::DATETIME_IMMUTABLE)]
        private DateTimeImmutable $created,
    ) {
        $this->id = new Ulid();
        $this->subject = mb_substr($subject, 0, 255);
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @return list<string>
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array<string, int>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
