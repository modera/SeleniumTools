<?php

namespace Modera\Component\SeleniumTools\Tests\Unit\VideoRecording;

use GuzzleHttp\Client;
use Modera\Component\SeleniumTools\Tests\Fixtures\PHPUnitRemoteReportingListenerStub;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class RemoteReportingListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PHPUnitRemoteReportingListenerStub
     */
    private $listener;

    private $clientMock;

    public function setUp()
    {
        $this->listener = new PHPUnitRemoteReportingListenerStub();

        $this->clientMock = \Phake::mock(Client::class);
        $this->listener->getGuzzleClientCallback = function() {
            return $this->clientMock;
        };

        $_SERVER['RRL_ENDPOINT'] = 'http://foo';
    }

    public function tearDown()
    {
        unset($_SERVER['RRL_ENDPOINT']);
    }

    public function testStartTest()
    {
        $this->listener->startTest($this);

        $url = null;
        \Phake::verify($this->clientMock)->post(\Phake::capture($url));

        $this->assertEquals('http://foo/tests/'.urlencode(get_class($this)), $url);
    }

    public function testEndTest()
    {
        $this->listener->endTest($this, null);

        $url = null;
        \Phake::verify($this->clientMock)->delete(\Phake::capture($url));

        $this->assertEquals('http://foo/tests/'.urlencode(get_class($this)), $url);
    }
}