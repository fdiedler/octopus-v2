<?php

use App\Tests\Fixtures\Http\ClientFactory;

/**
 * @author Benoit Del Basso <bdelbasso@users.noreply.github.com>
 */
trait OutgoingHttpTrait
{

    /**
     * @Given a :method call to :uri returns a :code with body from :file
     * @Given a :method call to :uri returns a :code
     */
    public function httpCallReturns($method, $uri, $code, $file = null)
    {
        $this->getTestClientFactory()->addMockedUrl($method, $uri, ClientFactory::responseFromFile($code, $file));
    }

    /**
     * @Given a :method call to :uri returns a :code redirection to :to
     */
    public function httpCallRedirectsTo($method, $uri, $code, $to)
    {
        $headers = [ 'Location' => $to ];
        $this->getTestClientFactory()->addMockedUrl($method, $uri, new \GuzzleHttp\Psr7\Response($code, $headers));
    }

    /**
     * @AfterScenario
     */
    public function cleanHttpMocks()
    {
        $this->getTestClientFactory()->clearMockedUrls();
    }

    private function getTestClientFactory()
    {
        return $this->get('App\Tests\Fixtures\Http\ClientFactory');
    }

}
