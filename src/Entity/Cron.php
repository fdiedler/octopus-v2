<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cron")
 * @ORM\Entity(repositoryClass="App\Repository\CronRepository")
 *
 * @author DIEDLER Florent <flornet@wehost.fr>
 */
class Cron
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100, unique=false)
     */
    private $name;

    /**
     * Details of the cron job
     *
     * @ORM\Column(type="array", nullable=true)
     * @var array
     */
    private $details;

    /**
     * @ORM\Column(name="finished", type="boolean")
     */
    private $finished;

    /**
     * The start datetime of the cron job
     *
     * @ORM\Column(type="datetime")
     * @var DateTime
     */
    protected $startDate;
    
    /**
     * The end datetime of the cron job
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @var DateTime
     */
    protected $endDate;
    
    public function __construct($name)
    {
        $this->name = $name;
        $this->finished = false;
        $this->details = [];
        $this->startDate = new \DateTime("now");
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
    public function setName(string $name) : Cron
    {
        $this->name = $name;
        return $this;
    }
    
    /**
     * @return DateTime
     */
    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }
    
    /**
     * @return DateTime
     */
    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }
    
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @param array $details
     * @return Invoice
     */
    public function setDetails(array $details) : Cron
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * @param bool $finished
     */
    public function setFinished(bool $finished) : Cron
    {
        $this->finished = $finished;
        $this->endDate = new \DateTime("now");
        return $this;
    }

    public function __toString()
    {
        return (string) $this->getName();
    }
}
