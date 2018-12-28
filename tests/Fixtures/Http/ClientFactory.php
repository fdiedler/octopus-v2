<?php

namespace App\Tests\Fixtures\Http;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use App\Http\ClientFactory as BaseClientFactory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
class ClientFactory extends BaseClientFactory
{

    public static $mockedResponses = [];
   
    public static function addMockedUrl($method, $url, ResponseInterface $response)
    {
        self::$mockedResponses[$method.' '.$url] = $response;
    }

    public static function clearMockedUrls()
    {
        self::$mockedResponses = [];
    }

    public function createClient(): ClientInterface {
        return new class() extends Client {
            public function request($method, $uri = '', array $options = [])
            {
                $mockedResponses = ClientFactory::$mockedResponses;
                if (!isset($mockedResponses[$method.' '.$uri])) {
                    throw new \Exception('Unexpected request (no mock): '.$method.' '.$uri."\nGot mocks for:".print_r(array_keys($mockedResponses), true));
                }

                return $mockedResponses[$method.' '.$uri];
            }
        };
    }

    public static function responseFromFile($code, $file)
    {
        if(null == $file) {
            return new Response($code);
        }

        $f = fopen(__DIR__.'/../../samples/'.$file, 'r');
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $typeMapping = [
            'html' => 'text/html',
            'json' => 'application/json',
        ];
        $headers = [
            'Content-Type' => $typeMapping[$extension] ?? 'text/plain'
        ];
        return new Response($code, $headers, new \GuzzleHttp\Psr7\Stream($f));
    }

}
