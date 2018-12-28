<?php

namespace App\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * Source of all HTTP clients. May be overriden by functional tests.
 *
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class ClientFactory
{

    /**
     * Creates a simple HTTP client (faster).
     *
     * @return ClientInterface
     */
    public function createClient(): ClientInterface {
        return new Client();
    }
}
