<?php

namespace Modera\Component\SeleniumTools\VideoRecording\RemotePHPUnitListener;

use Exception;
use GuzzleHttp\Client;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_TestSuite as TestSuite;

/**
 * Sends requests to
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class RemoteReportingListener implements \PHPUnit_Framework_TestListener
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @return Client
     */
    private function getGuzzleClient()
    {
        if (!$this->guzzleClient) {
            $this->guzzleClient = new Client();
        }

        return $this->guzzleClient;
    }

    /**
     * Returns an URL where requests must be sent to.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        $endpoint = isset($_SERVER['RRL_ENDPOINT']) ? $_SERVER['RRL_ENDPOINT'] : getenv('RRL_ENDPOINT');
        if (!$endpoint) {
            throw new \RuntimeException('Unable to resolve $SERVER_/environment variable "RRL_ENDPOINT".');
        }

        // RRL = Remote Reporting Listener
        return $endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function startTest(Test $test)
    {
        $url = $this->getEndpoint().'/tests/'.urlencode(get_class($test));

        $this->getGuzzleClient()->post($url);
    }

    /**
     * {@inheritdoc}
     */
    public function endTest(Test $test, $time)
    {
        $url = $this->getEndpoint().'/tests/'.urlencode(get_class($test));

        $this->getGuzzleClient()->delete($url);
    }

    // boilerplate:

    public function addError(Test $test, Exception $e, $time)
    {
    }

    public function addFailure(Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
    }

    public function addIncompleteTest(Test $test, Exception $e, $time)
    {
    }

    public function addSkippedTest(Test $test, Exception $e, $time)
    {
    }

    public function startTestSuite(TestSuite $suite)
    {
    }

    public function endTestSuite(TestSuite $suite)
    {
    }
}