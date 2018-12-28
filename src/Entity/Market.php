<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints;

/**
 * A Market (business zone)
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="App\Repository\MarketRepository")
 * @ORM\Table(name="market")
 * @Constraints\UniqueEntity(fields="name", repositoryMethod="testUniqueName")
 */
class Market
{
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id ;

    /**
     * Name of the host: e.g. John Doe
     *
     * @ORM\Column(type="string", length=100)
     * @var string
     */
    private $name ;

    /**
     * City manager.
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true)
     * @var User
     */
    private $manager;

    /**
     * Percentage that the manager takes on all the reservations
     * for this market.
     *
     * @ORM\Column(type="integer", nullable=true)
     * @var integer
     */
    private $managerPercentage ;

    /**
     * The properties located on this market.
     *
     * @ORM\OneToMany(targetEntity="Property", mappedBy="market")
     * @var Collection
     */
    private $properties ;
    
    /**
     * The cleaning presta located on this market.
     *
     * @ORM\OneToMany(targetEntity="CleaningPresta", mappedBy="market")
     * @var Collection
     */
    private $cleaningPresta;
    
    /**
     * The objectives associated to this market.
     *
     * @ORM\OneToMany(targetEntity="MarketObjective", mappedBy="market")
     * @var Collection
     */
    private $objectives;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->cleaningPresta = new ArrayCollection();
        $this->objectives = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
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
     * @return Host
     */
    public function setName($name): Market
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return User
     */
    public function getManager(): ?User
    {
        return $this->manager;
    }

    /**
     * @param User $manager
     */
    public function setManager(User $manager): Market
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return int
     */
    public function getManagerPercentage(): ?int
    {
        return $this->managerPercentage;
    }

    /**
     * @param int $managerPercentage
     */
    public function setManagerPercentage(int $managerPercentage): Market
    {
        $this->managerPercentage = $managerPercentage;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * @param Collection $properties
     * @return Market
     */
    public function setProperties(Collection $properties): Market
    {
        $this->properties = $properties;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getCleaningPresta(): Collection
    {
        return $this->cleaningPresta;
    }

    /**
     * @param Collection $cleaningPresta
     * @return Market
     */
    public function setCleaningPresta(Collection $cleaningPresta): Market
    {
        $this->cleaningPresta = $cleaningPresta;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getObjectives(): Collection
    {
        return $this->objectives;
    }

    /**
     * @param Collection $objectives
     * @return Market
     */
    public function setObjectives(Collection $objectives): Market
    {
        $this->objectives = $objectives;
        return $this;
    }

    public function __toString()
    {
        if(method_exists($this, "__load")) $this->__load();
        return (string) $this->name;
    }

}
