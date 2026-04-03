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

namespace SolidInvoice\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\UserBundle\Repository\UserRepository;
use SolidWorx\Platform\PlatformBundle\Security\TwoFactor\Traits\UserTwoFactor;
use SolidWorx\Platform\SaasBundle\Trial\TrialUserInterface;

#[ORM\Table(name: User::TABLE_NAME)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends \SolidWorx\Platform\PlatformBundle\Model\User implements TrialUserInterface
{
    final public const string TABLE_NAME = 'users';

    use TimeStampable;
    use UserTwoFactor;

    /**
     * @var Collection<int, ApiToken>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ApiToken::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private Collection $apiTokens;

    /**
     * @var Collection<int, Company>
     */
    #[ORM\ManyToMany(targetEntity: Company::class, inversedBy: 'users', cascade: ['persist'])]
    private Collection $companies;

    /**
     * @deprecated This should not be used anymore. Remove once all usages are gone.
     */
    public ?string $plainPassword = null;

    public function __construct()
    {
        parent::__construct();
        $this->apiTokens = new ArrayCollection();
        $this->companies = new ArrayCollection();
    }

    /**
     * @return Collection<int, ApiToken>
     */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    /**
     * @param Collection<int, ApiToken> $apiTokens
     */
    public function setApiTokens(Collection $apiTokens): static
    {
        $this->apiTokens = $apiTokens;

        return $this;
    }

    /**
     * @return Collection<int, Company>
     */
    public function getCompanies(): Collection
    {
        return $this->companies;
    }

    public function addCompany(Company $company): static
    {
        if (! $this->companies->contains($company)) {
            $this->companies[] = $company;
        }

        return $this;
    }

    public function removeCompany(Company $company): static
    {
        if ($this->companies->contains($company)) {
            $this->companies->removeElement($company);
        }

        return $this;
    }
}
