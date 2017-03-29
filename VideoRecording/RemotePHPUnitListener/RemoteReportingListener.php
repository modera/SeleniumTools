<?php

namespace Modera\Component\SeleniumTools\VideoRecording\RemotePHPUnitListener;

use Exception;
use Modera\Component\SeleniumTools\VideoRecording\GuzzleClientTrait;
use PHPUnit_Framework_AssertionFailedError;
use PHPUnit_Framework_Test as Test;
use PHPUnit_Framework_TestSuite as TestSuite;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class RemoteReportingListener implements \PHPUnit_Framework_TestListener
{
    use GuzzleClientTrait;

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

    public function addRiskyTest(Test $test, Exception $e, $time)
    {
    }
}