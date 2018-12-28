<?php

namespace App\Reference;

/**
 * Generator of unique references (typically for an entity)
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class ReferenceGenerator
{

    public function generate() {
        $prefix = substr(md5(gethostname()),4);
        return uniqid($prefix);
    }

}
