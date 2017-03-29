<?php

namespace Modera\Component\SeleniumTools\VideoRecording\FFMPEGServer;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class Server
{
    /**
     * URL pattern => method prefix to invoke, the method name will be later suffixed with name of HTTP method endpoint
     * is invoked with.
     *
     * @var array
     */
    private $actions = array(
        '@/tests/(?<name>.*)@' => 'onTests'
    );

    /**
     * @var callable
     */
    private $commandExecutor;

    /**
     * @param callable $commandExecutor  A function that will run commands in host OS to start/stop ffmpeg recording
     */
    public function __construct(callable $commandExecutor = null)
    {
        $this->commandExecutor = $commandExecutor;
    }

    /**
     * @param array $server  Usually will be a global $_SERVER variable
     */
    public function handleRequest(array $server)
    {
        if (!isset($server['REQUEST_URI']) || '' == $server['REQUEST_URI']) {
            throw new \InvalidArgumentException('Given $server does not have information about "REQUEST_URI".');
        }

        $uri = $server['REQUEST_URI'];
        $method = isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET';

        $serverClassMethods = get_class_methods($this);

        foreach ($this->actions as $regex=>$methodPrefix) {
            if (preg_match($regex, $uri, $matches)) {
                $methodName = $methodPrefix.ucfirst(strtolower($method));
                if (in_array($methodName, $serverClassMethods)) {
                    return $this->$methodName($matches);
                }
            }
        }

        return array(
            'headers' => [
                "HTTP/1.0 404 Not Found"
            ],
            'body' => "Server cannot handle endpoint <b>$uri</b> with method <b>$method</b>."
        );
    }

    /**
     * @param array $response
     */
    public static function sendResponse($response)
    {
        if (is_array($response)) {
            if (isset($response['headers'])) {
                foreach ($response['headers'] as $header) {
                    header($header);
                }
            }

            if (isset($response['body'])) {
                echo $response['body'];
            }
        }

        // otherwise sending nothing back
    }

    /**
     * Action.
     *
     * @param array $urlParams
     *
     * @return array
     */
    private function onTestsPost(array $urlParams)
    {
        $tmuxSessionName = $this->normalizeTestCaseName($urlParams['name']);

        $videoFilename = $this->normalizeTestCaseName($urlParams['name']).'.mp4';
        if (file_exists($videoFilename)) {
            unlink($videoFilename);
        }

        $cmd = sprintf(
            "tmux new-session -d -s %s 'ffmpeg -f x11grab -video_size 1920x1080 -i selenium-node:44 -codec:v libx264 -r 15 %s'",
            $tmuxSessionName,
            $videoFilename
        );

        $this->execCommand($cmd);

        return array(
            'body' => json_encode(array('filename' => $videoFilename), JSON_PRETTY_PRINT),
        );
    }

    /**
     * Action.
     *
     * @param array $urlParams
     *
     * @return array
     */
    private function onTestsDelete(array $urlParams)
    {
        $tmuxSessionName = $this->normalizeTestCaseName($urlParams['name']);

        $cmd = sprintf(
            'tmux send-keys -t %s q',
            $tmuxSessionName
        );

        $this->execCommand($cmd);

        $filename = $this->normalizeTestCaseName($urlParams['name']).'.mp4';

        return array(
            'body' => json_encode(array('filename' => $filename, JSON_PRETTY_PRINT)),
        );
    }

    /**
     * @param string $command
     */
    private function execCommand($command)
    {
        file_put_contents(__DIR__.'/commands', $command, FILE_APPEND);

        if ($this->commandExecutor) {
            call_user_func($this->commandExecutor, $command, $this);
        } else {
            exec($command);
        }
    }

    private function normalizeTestCaseName($encodedTestCaseName)
    {
        $result = str_replace(
            ['\\', ' '], '_', preg_replace('~(?<=\\w)([A-Z])~', '-$1', $this->decodeName($encodedTestCaseName))
        );

        // Modera\ChatTest -> modera.chat-test
        // This is scenario name-this is feature name -> this_is_scenario_name-this_is_feature_name
        return strtolower($result);
    }

    private function decodeName($encodedTestCaseName)
    {
        return trim(urldecode($encodedTestCaseName));
    }
}