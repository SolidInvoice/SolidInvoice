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

namespace SolidInvoice\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Stringable;
use Symfony\Component\Serializer\Annotation as Serialize;

/**
 * SolidInvoice\ClientBundle\Entity\AdditionalContactDetail.
 *
 * @ORM\Entity()
 * @ORM\Table(name="contact_details")
 */
class AdditionalContactDetail implements Stringable
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(name="id", type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    protected ?UuidInterface $id = null;

    /**
     * @ORM\Column(name="value", type="text")
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    protected ?string $value = null;

    /**
     * @ORM\ManyToOne(targetEntity="ContactType", inversedBy="details")
     * @ORM\JoinColumn(name="contact_type_id")
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    protected ?ContactType $type = null;

    /**
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="additionalContactDetails")
     * @ORM\JoinColumn(name="contact_id")
     * @Serialize\Groups({"js"})
     */
    private ?Contact $contact = null;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getType(): ?ContactType
    {
        return $this->type;
    }

    public function setType(?ContactType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
