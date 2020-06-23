<?php

namespace Modera\Component\SeleniumTools\Behat;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverBrowserType;
use Modera\Component\SeleniumTools\Actor;
use Modera\Component\SeleniumTools\DriverFactoryInterface;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class BehatDriverFactory implements DriverFactoryInterface
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $harnessName;

    /**
     * @param array $config
     * @param string $harnessName
     */
    public function __construct(array $config, $harnessName)
    {
        $this->config = $config;
        $this->harnessName = $harnessName;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(Actor $actor)
    {
        if (!isset($this->config['harnesses'][$this->harnessName])) {
            throw InvalidConfigException::create(
                sprintf('Unable to find a harness "%s" in config.', $this->harnessName),
                '/'
            );
        }
        $harnessConfig = $this->config['harnesses'][$this->harnessName];

        if (!isset($this->config['drivers'][$harnessConfig['driver']])) {
            $msg = sprintf(
                'Harness "%s" configuration relies on a driver "%s" which is not declared in config.',
                $this->harnessName, $harnessConfig['driver']
            );
            throw InvalidConfigException::create($msg, '/harnesses/'.$this->harnessName);
        }
        $driverConfig = $this->config['drivers'][$harnessConfig['driver']];

        if (!in_array($driverConfig['browser'], get_class_methods(DesiredCapabilities::class))) {
            $msg = sprintf(
                'Unknown browser config "%s" is requested. See DesiredCapabilities class for a list of available options.',
                $driverConfig['browser']
            );
            throw InvalidConfigException::create($msg, "/drivers[{$harnessConfig['driver']}]");
        }
        $reflClass = new \ReflectionClass(DesiredCapabilities::class);
        $reflMethod = $reflClass->getMethod($driverConfig['browser']);
        $capabilities = $reflMethod->invoke(null);

        $capabilities->getCapability(ChromeOptions::CAPABILITY)->addArguments(array(
            '--window-size=1280,1000', '--accept-ssl-certs=true'
        ));

        return $this->doCreateDriver($actor, $driverConfig, $capabilities);
    }

    protected function doCreateDriver(Actor $actor, $driverConfig, $capabilities)
    {
        return RemoteWebDriver::create(
            $driverConfig['host'],
            $capabilities,
            $driverConfig['connection_timeout'],
            $driverConfig['request_timeout']
        );
    }
}