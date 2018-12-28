<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints;

/**
 * @ORM\Entity
 * @ORM\Table(name="checker")
 * @ORM\Entity(repositoryClass="App\Repository\CheckerRepository")
 * @Constraints\UniqueEntity(fields="name", repositoryMethod="testUniqueName")
 * @Constraints\UniqueEntity(fields="user", repositoryMethod="testUniqueUser")
 *
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class Checker
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
    private $name;

    /**
     * The Market where this cleaning presta is located
     *
     * @ORM\ManyToOne(targetEntity="Market", inversedBy="cleaningPresta")
     * @ORM\JoinColumn(nullable=false)
     * @var Market
     */
    private $market;
    
    /**
     * The User that owns this checkin
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true)
     * @var User
     */
    private $user;
    
    /**
     * All the checkins associated to this checker
     *
     * @ORM\OneToMany(targetEntity="Checkin", mappedBy="checker")
     * @var Collection
     */
    private $checkins;
    
    /**
     * Base amount for a checkin (without taxes, euros, cents).
     *
     * @ORM\Column(type="string", length=10)
     * @var string
     */
    private $amountWithoutTaxes;

    // Virtual property not persist in Database
    // Only used for creating the User account associated to this Checker
    private $virtualUserLogin;
    
    public function __construct()
    {
        $this->checkins = new ArrayCollection();
    }

    public function getVirtualUserLogin()
    {
        return $this->virtualUserLogin;
    }
    
    public function setVirtualUserLogin($email) : Checker
    {
        $this->virtualUserLogin = $email;
        return $this;
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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): Checker
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Market
     */
    public function getMarket(): ?Market
    {
        return $this->market;
    }

    /**
     * @param Market $market
     */
    public function setMarket(Market $market): Checker
    {
        $this->market = $market;
        return $this;
    }
    
    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): Checker
    {
        $this->user = $user;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getCheckins(): Collection
    {
        return $this->checkins;
    }

    /**
     * @param Collection $checkins
     */
    public function setCheckins(Collection $checkins): Checker
    {
        $this->checkins = $checkins;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getAmountWithoutTaxes(): ?string
    {
        return $this->amountWithoutTaxes;
    }

    /**
     * @param string $amount
     */
    public function setAmountWithoutTaxes(string $amount): Checker
    {
        $this->amountWithoutTaxes = $amount;
        return $this;
    }
    
    public function __toString()
    {
        return (string) $this->name;
    }
}
