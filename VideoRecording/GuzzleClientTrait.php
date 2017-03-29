<?php

namespace Modera\Component\SeleniumTools\VideoRecording;

use GuzzleHttp\Client;

/**
 * @internal
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
trait GuzzleClientTrait
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @return Client
     */
    protected function getGuzzleClient()
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
}