<?php namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints;

/**
 * Objectives associated to a Market
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="App\Repository\MarketObjectiveRepository")
 * @ORM\Table(name="market_objective")
 */
class MarketObjective
{
    const OBJECTIVE_TYPE = [
        "maxReservation"               => ["type" => 0, "label" => "Objective - Nb reservations"],
        "maxTurnover"                  => ["type" => 1, "label" => "Objective - Turnover"],
    ];
    
    /**
    * @ORM\Column(type="integer")
    * @ORM\Id
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    private $id;

    /**
     * Type of the objective
     *
     * @ORM\Column(type="integer")
     * @var int
     */
    private $type;

    /**
     * Objective date
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    private $date;
    
    /**
    * @ORM\Column(type="integer")
    * @var integer
    */
    private $value;

    /**
     * The market
     *
     * @ORM\ManyToOne(targetEntity="Market", inversedBy="objectives")
     * @ORM\JoinColumn(nullable=false)
     * @var Market
     */
    private $market;

    /**
     * Constructor.
     */
    public function __construct()
    {
        
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return MarketObjective
     */
    public function setType($type): MarketObjective
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * @return string
     */
    public function getCorrelType(): ?string
    {
        foreach (self::OBJECTIVE_TYPE as $objective)
        {
            if ($objective["type"] == $this->type)
                return $objective["label"];
        }
        return null;
    }

    /**
     * @return DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return MarketObjective
     */
    public function setDate(\DateTime $date): MarketObjective
    {
        $this->date = $date;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getValue(): ?int
    {
        return $this->value;
    }

    /**
     * @param int $value
     * @return Host
     */
    public function setValue($value): MarketObjective
    {
        $this->value = $value;
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
     * @return Market
     */
    public function setMarket(Market $market): MarketObjective
    {
        $this->market = $market;
        return $this;
    }
    
    public function __toString()
    {
        return $this->getCorrelType();
    }
}
