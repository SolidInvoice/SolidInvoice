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

namespace SolidInvoice\ApiBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\Repository\WebhookRepository;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'api_webhooks')]
#[ORM\Entity(repositoryClass: WebhookRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['webhook_api:read']],
    denormalizationContext: ['groups' => ['webhook_api:write']],
)]
class Webhook
{
    use CompanyAware;
    use TimeStampable;

    public const SUPPORTED_EVENTS = [
        'invoice.created',
        'invoice.paid',
        'invoice.sent',
        'quote.accepted',
        'quote.created',
        'quote.sent',
    ];

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['webhook_api:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 2048)]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Groups(['webhook_api:read', 'webhook_api:write'])]
    private string $url = '';

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Assert\NotBlank]
    #[Assert\All([new Assert\Choice(choices: self::SUPPORTED_EVENTS)])]
    #[Groups(['webhook_api:read', 'webhook_api:write'])]
    private array $events = [];

    #[ORM\Column(type: Types::STRING, length: 64)]
    #[Groups(['webhook_api:read'])]
    #[ApiProperty(writable: false)]
    private string $secret;

    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['webhook_api:read', 'webhook_api:write'])]
    private bool $active = true;

    public function __construct()
    {
        $this->secret = bin2hex(random_bytes(32));
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param list<string> $events
     */
    public function setEvents(array $events): self
    {
        $this->events = $events;

        return $this;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
