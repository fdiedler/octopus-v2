<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints;

/**
 * @ORM\Entity
 * @ORM\Table(name="cleaning_presta")
 * @ORM\Entity(repositoryClass="App\Repository\CleaningPrestaRepository")
 * @Constraints\UniqueEntity(fields="name", repositoryMethod="testUniqueName")
 * @Constraints\UniqueEntity(fields="user", repositoryMethod="testUniqueUser")
 *
 * @author DIEDLER Florent <florent@wehost.fr>
 */
class CleaningPresta
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
     * The User that owns this cleaning
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true)
     * @var User
     */
    private $user;
    
    /**
     * All the cleanings associated to this cleaning presta
     *
     * @ORM\OneToMany(targetEntity="Cleaning", mappedBy="presta")
     * @var Collection
     */
    private $cleanings;

    public function __construct()
    {
        $this->cleanings = new ArrayCollection();
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
    public function setName(string $name): CleaningPresta
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
    public function setMarket(Market $market): CleaningPresta
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
    public function setUser(User $user): CleaningPresta
    {
        $this->user = $user;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getCleanings(): Collection
    {
        return $this->cleanings;
    }

    /**
     * @param Collection $cleanings
     */
    public function setCleanings(Collection $cleanings): CleaningPresta
    {
        $this->cleanings = $cleanings;
        return $this;
    }
    
    public function __toString()
    {
        return (string) $this->name;
    }
}
