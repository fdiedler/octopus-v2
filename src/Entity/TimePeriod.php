<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * A period in time.
 *
 * @ORM\Entity
 * @ORM\Table(name="time_period")
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class TimePeriod
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * The start of the period
     *
     * @ORM\Column(type="date")
     * @var DateTime
     */
    protected $start;

    /**
     * The end of the period (may be null to indicate that
     * the period is still running and matches any event after the start date).
     *
     * @ORM\Column(type="date", nullable=true)
     * @var DateTime|null
     */
    protected $end;

    public function __construct(DateTime $start, DateTime $end = null)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return DateTime
     */
    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    /**
     * @param DateTime $start
     */
    public function setStart(DateTime $start): TimePeriod
    {
        $this->start = $start;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    /**
     * @param DateTime|null $end
     */
    public function setEnd($end): TimePeriod
    {
        $this->end = $end;
        return $this;
    }

    public function contains(DateTime $date): bool
    {
        return $this->start <= $date && ($this->end == null || $date <= $this->end);
    }

}
