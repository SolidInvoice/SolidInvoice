<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\User\AdvancedUserInterface,
	Symfony\Component\Security\Core\User\EquatableInterface,
	Symfony\Component\Security\Core\Util\StringUtils,
	Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * CS\UserBundle\Entity\User
 *
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="CSBill\UserBundle\Repository\UserRepository")
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User implements AdvancedUserInterface, EquatableInterface, \Serializable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $username
     *
     * @ORM\Column(type="string", length=25, unique=true)
     * @Assert\NotBlank()
     * @Assert\MinLength(limit=3)
     */
    protected $username;

    /**
     * @var string $salt
     *
     * @ORM\Column(type="string", length=32)
     */
    protected $salt;

    /**
     * @var string $password
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     */
    protected $password;

    /**
     * @var string $email
     *
     * @ORM\Column(type="string", length=60, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    /**
     * @var boolean $active
     * @ORM\Column(name="active", type="boolean")
     */
    protected $active;

    /**
     * @var ArrayCollection $roles
     *
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users", cascade={"ALL"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @Assert\Valid()
     */
    protected $roles;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->active = true;
        $this->setSalt(md5(uniqid(null, true)));
        $this->roles = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param  string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set salt
     *
     * @param  string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set password
     *
     * @param  string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param  string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set active
     *
     * @param  boolean|integer $active
     * @return boolean
     */
    public function setActive($active)
    {
        return $this->active = (boolean) $active;
    }

    /**
     * Get active
     *
     * @return boolean
     */
    public function getActive()
    {
        return $this->isActive();
    }

    /**
     * is active
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->active ? true : false;
    }

    public function eraseCredentials()
    {

    }

    public function isEqualTo(UserInterface $user)
    {
    	return StringUtils::equals($this->username, $user->getUsername());
    }

    /**
     * Add role
     *
     * @param  Role $role
     * @return User
     */
    public function addRole(Role $role)
    {
        $this->roles[] = $role;
        $role->addUser($this);

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * Check if user account is not expired
     *
     * @return boolean
     */
    public function isAccountNonExpired()
    {
        // TODO : check if user account has not expired
        return true;
    }

    /**
     * Check if user account is locked
     *
     * @return boolean
     */
    public function isAccountNonLocked()
    {
        // TODO : check if user account is locked
        return true;
    }

    /**
     * Check if user login credentials expired
     *
     * @return boolean
     */
    public function isCredentialsNonExpired()
    {
        // TODO : check if user credentials expired
        return true;
    }

    /**
     * Check if the user is active
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->isActive();
    }

    public function serialize()
    {
        return json_encode(array($this->id, $this->username, $this->salt, $this->password, $this->email, $this->active, $this->created, $this->updated, $this->deleted));
    }

    public function unserialize($object)
    {
        return list($this->id, $this->username, $this->salt, $this->password, $this->email, $this->active, $this->created, $this->updated, $this->deleted) = json_decode($object);
    }
}
