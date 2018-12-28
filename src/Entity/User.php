<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="array")
     */
    private $roles;
    
    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $allowedMarketId;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private $preferences;
    
    /**
     * @ORM\Column(name="enabled", type="boolean")
     */
    private $enabled;
    
    public function __construct()
    {
        $this->enabled = true;
        $this->roles = [ 'ROLE_USER' ];
        $this->allowedMarketId = [];
        $this->preferences = [];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * Use string as parameter to allow setting only one ROLE_CHECKER
     * but this value is stored as an array in Database
     * @param string $roles
     */
    public function setRoles(string $roles)
    {
        $this->roles = [$roles];
    }
    
    /**
     * @return array
     */
    public function getPreferences(): ?array
    {
        return $this->preferences;
    }

    /**
     * @param array $preferences
     */
    public function setPreferences(array $preferences) : User
    {
        $this->preferences = $preferences;
        return $this;
    }
    
    /**
     * @return array
     */
    public function getAllowedMarketId(): ?array
    {
        return $this->allowedMarketId;
    }

    /**
     * @param array $allowedMarketId
     */
    public function setAllowedMarketId(array $allowedMarketId)
    {
        $this->allowedMarketId = $allowedMarketId;
    }

    public function hasCheckerAccess()
    {
        return $this->hasCheckerAndCleaningAccess() || in_array('ROLE_CHECKER', $this->roles);
    }
    
    public function hasCleaningAccess()
    {
        return $this->hasCheckerAndCleaningAccess() || in_array('ROLE_CLEANING', $this->roles);
    }
    
    public function hasCheckerAndCleaningAccess()
    {
        return in_array('ROLE_CLEANING_CHECKER', $this->roles);
    }
    
    public function isOutsideUser()
    {
        return ($this->hasCheckerAccess() || $this->hasCleaningAccess() || $this->hasCheckerAndCleaningAccess());
    }

    // ### https://symfony.com/doc/current/security/entity_provider.html#create-your-user-entity

    public function getUsername()
    {
        return $this->email;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        return null;
    }

    public function getPassword()
    {
        // login with password is not allowed
        return null;
    }

    public function eraseCredentials()
    {
        // nothing to do
    }


    // ### https://symfony.com/doc/current/security/entity_provider.html#forbid-inactive-users-advanceduserinterface

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    // ###


    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->enabled
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->enabled
            ) = unserialize($serialized);
    }

    public function __toString()
    {
        return (string) $this->email;
    }
}
