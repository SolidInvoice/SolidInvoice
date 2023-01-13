<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\UserBundle\Entity\User;
use Stringable;

/**
 * @ORM\Entity(repositoryClass=CompanyRepository::class)
 * @ORM\Table(name="companies")
 */
class Company implements Stringable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="companies")
     */
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }


    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Company
    {
        $this->name = $name;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): Company
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): Company
    {
        if ($this->users->removeElement($user)) {
            $user->removeCompany($this);
        }

        return $this;
    }
}
