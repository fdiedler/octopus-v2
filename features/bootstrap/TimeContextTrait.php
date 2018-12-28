<?php

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
trait TimeContextTrait
{

    /**
     * @Transform :start
     * @Transform :end
     * @Transform :date
     */
    public function fixDate(string $value): DateTime
    {
        $date = DateTime::createFromFormat("Y-m-d H:i:s",$value." 00:00:00");
        if(false === $date) {
            throw new \Exception("Not a valid date: $value");
        }
        return $date;
    }

}
