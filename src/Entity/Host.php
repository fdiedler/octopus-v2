<?php namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints;
use DateTime;

/**
 * A Host (WeHost customer with properties to rent on the BnB platform).
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="App\Repository\HostRepository")
 * @ORM\Table(name="host")
 * @Constraints\UniqueEntity(fields="email", repositoryMethod="testUniqueEmail")
 * @Constraints\UniqueEntity(fields="wehostEmailAlias", repositoryMethod="testUniqueEmail")
 */
class Host
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
     * (Real) email of this host so that we can contact him/her
     *
     * @ORM\Column(type="string", length=255, unique=false)
     * @var string
     */
    private $email;
    
    /**
     * Date
     *
     * @ORM\Column(type="date", nullable=true)
     * @var DateTime
     */
    private $birthdate;

    /**
     * Alias email created by WeHost for this host (used as Airbnb username)
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @var string
     */
    private $wehostEmailAlias ;

    /**
     * Password on Airbnb account
     *
     * @ORM\Column(type="string", length=32)
     * @var string
     */
    private $airbnbPassword ;

    /**
     * All the properties of this host
     *
     * @ORM\OneToMany(targetEntity="Property", mappedBy="host", cascade={"remove"})
     * @var Collection
     */
    private $properties ;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->properties = new ArrayCollection();
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
    public function setName($name): Host
    {
        $this->name = $name;
        return $this;
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
     * @return Host
     */
    public function setEmail($email): Host
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getBirthdate(): ?DateTime
    {
        return $this->birthdate;
    }

    /**
     * @param DateTime $birthdate
     */
    public function setBirthdate(?DateTime $birthdate): Host
    {
        $this->birthdate = $birthdate;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getWehostEmailAlias(): ?string
    {
        return $this->wehostEmailAlias;
    }

    /**
     * @param string $wehostEmailAlias
     * @return Host
     */
    public function setWehostEmailAlias($wehostEmailAlias): Host
    {
        $this->wehostEmailAlias = $wehostEmailAlias;
        return $this;
    }

    /**
     * @return string
     */
    public function getAirbnbPassword(): ?string
    {
        return $this->airbnbPassword;
    }

    /**
     * @param string $airbnbPassword
     * @return Host
     */
    public function setAirbnbPassword($airbnbPassword): Host
    {
        $this->airbnbPassword = $airbnbPassword;
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
     */
    public function setProperties(Collection $properties): Host
    {
        $this->properties = $properties;
        return $this;
    }

    public function isCurrentlyManaged()
    {
        foreach($this->getProperties() as $property) {
            if($property->isCurrentlyManaged()) {
                return true;
            }
        }

        return false;
    }

    public function __toString()
    {
        if(method_exists($this, "__load")) $this->__load();
        return $this->name;
    }
}
