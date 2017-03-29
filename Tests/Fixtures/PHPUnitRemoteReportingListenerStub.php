<?php

namespace Modera\Component\SeleniumTools\Tests\Fixtures;

use Modera\Component\SeleniumTools\VideoRecording\RemotePHPUnitListener\RemoteReportingListener;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class PHPUnitRemoteReportingListenerStub extends RemoteReportingListener
{
    public $getGuzzleClientCallback;

    protected function getGuzzleClient()
    {
        return call_user_func($this->getGuzzleClientCallback);
    }
}