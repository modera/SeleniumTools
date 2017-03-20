<?php

namespace Modera\Component\SeleniumTools\Behat;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\PageObjects\MJRBackendPageObject;
use Modera\Component\SeleniumTools\Querying\ExtDeferredQueryHandler;
use Modera\Component\SeleniumTools\TestHarness;

/**
 * @internal
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class TestHarnessFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $harnessName
     *
     * @return TestHarness
     */
    public function createHarness($harnessName)
    {
        $harnessesConfig = $this->config['harnesses'];
        if (!isset($harnessesConfig[$harnessName])) {
            throw new \RuntimeException("Unable to find a harness with name '$harnessName'.");
        }

        $harnessConfig = $harnessesConfig[$harnessName];

        $harness = new TestHarness(
            $harnessName,
            array(WebDriverCapabilityType::BROWSER_NAME => $harnessConfig['browser'])
        );

        foreach ($harnessConfig['actors'] as $name => $actorConfig) {
            $harness->addActor($name, $actorConfig['base_url']);
        }

        return $harness;
    }
}