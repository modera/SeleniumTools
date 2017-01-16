<?php

namespace Modera\Component\SeleniumTools\Tests\VideoRecording\FFMPEGServer;

use Modera\Component\SeleniumTools\VideoRecording\FFMPEGServer\Server;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ServerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleRequest_noRequestUri()
    {
        $server = new Server();
        $server->handleRequest(array());
    }

    public function testHandleRequest_startRecording()
    {
        $executedCommands = [];

        $exec = function($cmd) use(&$executedCommands) {
            $executedCommands[] = $cmd;
        };

        $s = new Server($exec);

        $result = $s->handleRequest(array(
            'REQUEST_URI' => sprintf('/tests/%s', urlencode('Modera\\ChatTest')),
            'REQUEST_METHOD' => 'POST'
        ));

        $this->assertValidResponseBodyWithFilename($result, 'modera_chat-test.mp4');

        $body = json_decode($result['body'], true);

        $this->assertEquals(1, count($executedCommands));
        $this->assertContains($body['filename'], $executedCommands[0]);
        $this->assertContains('-s modera_chat-test', $executedCommands[0]); // tmux session name
    }

    public function testHandleRequest_stopRecording()
    {
        $executedCommands = [];

        $exec = function($cmd) use(&$executedCommands) {
            $executedCommands[] = $cmd;
        };

        $s = new Server($exec);

        $result = $s->handleRequest(array(
            'REQUEST_URI' => sprintf('/tests/%s', urlencode('Modera\\ChatTest')),
            'REQUEST_METHOD' => 'DELETE'
        ));

        $this->assertValidResponseBodyWithFilename($result, 'modera_chat-test.mp4');

        $this->assertEquals(1, count($executedCommands));
        $this->assertContains('-t modera_chat-test', $executedCommands[0]); // tmux session name
    }

    private function assertValidResponseBodyWithFilename($rawResponse, $expectedFilename)
    {
        $this->assertTrue(is_array($rawResponse));
        $this->assertArrayHasKey('body', $rawResponse);

        $body = json_decode($rawResponse['body'], true);

        $this->assertArrayHasKey('filename', $body);
        $this->assertEquals($expectedFilename, $body['filename']);
    }
}