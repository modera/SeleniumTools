<?php

namespace Modera\Component\SeleniumTools\Behat;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;
use Behat\Behat\EventDispatcher\Event\ScenarioLikeTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Hook\Call\BeforeScenario;
use Doctrine\Common\Inflector\Inflector;
use Modera\Component\SeleniumTools\VideoRecording\GuzzleClientTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use GuzzleHttp\Client;

/**
 * @internal
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class RemoteReportingListener implements EventSubscriberInterface
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     * @param Client $guzzleClient
     */
    public function __construct(array $config, Client $guzzleClient = null)
    {
        $this->config = $config;
        $this->guzzleClient = $guzzleClient;
    }

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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScenarioTested::BEFORE => array('onBeforeScenario'),
            ScenarioTested::AFTER => array('onAfterScenario'),
        );
    }

    /**
     * @internal
     */
    public function onBeforeScenario(ScenarioLikeTested $event)
    {
        $url = $this->config['host'].'/tests/'.$this->formatTestName($event);

        $this->getGuzzleClient()->post($url);
    }

    /**
     * @internal
     */
    public function onAfterScenario(ScenarioLikeTested $event)
    {
        $url = $this->config['host'].'/tests/'.$this->formatTestName($event);

        $this->getGuzzleClient()->delete($url);
    }

    private function formatTestName(ScenarioLikeTested $event)
    {
        $left = $event->getFeature()->getTitle();
        $right = $event->getScenario()->getTitle();

        return urlencode($left.'-'.$right);
    }
}