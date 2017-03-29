<?php

namespace Modera\Component\SeleniumTools\Tests\Unit\Behat;

use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;
use GuzzleHttp\Client;
use Modera\Component\SeleniumTools\Behat\RemoteReportingListener;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class RemoteReportingListenerTest extends \PHPUnit_Framework_TestCase
{
    private $clientMock;

    public function setUp()
    {
        $this->clientMock = \Phake::mock(Client::class);
    }

    public function testOnBeforeScenario()
    {
        $config = array('host' => 'foo-host');

        $listener = new RemoteReportingListener($config, $this->clientMock);

        $scenario = 'That foo $ feature';
        $feature = 'That bar scenario.';

        $event = $this->createEventMock($scenario, $feature);
        $listener->onBeforeScenario($event);

        \Phake::verify($this->clientMock)
            ->post('foo-host/tests/'.urlencode($scenario.'-'.$feature))
        ;
    }

    public function testOnAfterScenario()
    {
        $config = array('host' => 'foo-host');

        $listener = new RemoteReportingListener($config, $this->clientMock);

        $scenario = 'That foo $ feature';
        $feature = 'That bar scenario.';

        $event = $this->createEventMock($scenario, $feature);
        $listener->onAfterScenario($event);

        \Phake::verify($this->clientMock)
            ->delete('foo-host/tests/'.urlencode($scenario.'-'.$feature))
        ;
    }

    private function createEventMock($featureTitle, $scenarioTitle)
    {
        $featureMock = \Phake::mock(FeatureNode::class);
        \Phake::when($featureMock)
            ->getTitle()
            ->thenReturn($featureTitle)
        ;
        $scenarioMock = \Phake::mock(ScenarioInterface::class);
        \Phake::when($scenarioMock)
            ->getTitle()
            ->thenReturn($scenarioTitle)
        ;

        $event = \Phake::mock(ScenarioLikeTested::class);
        \Phake::when($event)
            ->getFeature()
            ->thenReturn($featureMock)
        ;
        \Phake::when($event)
            ->getScenario()
            ->thenReturn($scenarioMock)
        ;

        return $event;
    }
}