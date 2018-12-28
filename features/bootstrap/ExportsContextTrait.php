<?php

use PHPUnit\Framework\Assert;
use App\Entity\User;
use App\Entity\Market;

/**
 * @author Benoit Del Basso <benoit.delbasso@mylittleparis.com>
 */
trait ExportsContextTrait
{

    /**
     * @Then I can export all the reservations
     */
    public function iCanExportAllTheReservations() {
        $tmpFile = tempnam(sys_get_temp_dir(),'exports');
        unlink($tmpFile);
        $this->get('App\Domain\Exports')->exportReservations($tmpFile);
        Assert::assertFileExists($tmpFile);
        unlink($tmpFile);

    }

}
