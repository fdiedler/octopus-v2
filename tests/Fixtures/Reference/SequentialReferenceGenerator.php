<?php

namespace App\Tests\Fixtures\Reference;

use App\Reference\ReferenceGenerator as BaseGenerator;

/**
 *  @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class SequentialReferenceGenerator extends BaseGenerator
{
    private $sequence = 0;

    public function generate()
    {
        ++$this->sequence;

        return ''.$this->sequence;
    }
}
