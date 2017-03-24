<?php

namespace Modera\Component\SeleniumTools;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\Exceptions\ActorExecutionException;

/**
 * Attempts to resolve driver configuration process using environmental variables:
 * - SELENIUM_HOST
 * - SELENIUM_CONNECTION_TIMEOUT
 * - SELENIUM_REQUEST_TIMEOUT
 * - SELENIUM_BROWSER
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class EnvironmentAwareDriverFactory implements DriverFactoryInterface
{
    /**
     * @param Actor $actor
     *
     * @return RemoteWebDriver
     */
    public function createDriver(Actor $actor)
    {
        $config = array(
            'host' => $this->resolveConfigValue('SELENIUM_HOST', 'http://localhost:4444/wd/hub'),
            'connection_timeout' => $this->resolveConfigValue('SELENIUM_CONNECTION_TIMEOUT', 30 * 1000),
            'request_timeout' => $this->resolveConfigValue('SELENIUM_REQUEST_TIMEOUT', 15 * 10000),
            'browser' => $this->resolveConfigValue('SELENIUM_BROWSER', 'chrome')
        );

        $reflClass = new \ReflectionClass(DesiredCapabilities::class);
        if (!$reflClass->hasMethod($config['browser'])) {
            throw new \RuntimeException();
        }
        $reflMethod = $reflClass->getMethod($config['browser']);
        $capabilities = $reflMethod->invoke(null);

        try {
            return $this->doCreateDriver($config, $capabilities);
        } catch (\Exception $e) {
            $msg = sprintf(
                'Actor "%s" was unable to establish connection with Selenium: "%s".',
                $actor->getName(), $e->getMessage()
            );

            throw new ActorExecutionException($msg, null, $e);
        }
    }

    protected function doCreateDriver(array $config, $capabilities)
    {
        return RemoteWebDriver::create(
            $config['host'], $capabilities, $config['connection_timeout'], $config['request_timeout']
        );
    }

    /**
     * @param string $key
     * @param string $fallbackValue
     *
     * @return string
     */
    private function resolveConfigValue($key, $fallbackValue)
    {
        // $_SERVER values have priority because they might be easily overridden through things like phpunit.xml.dist.
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $envValue = getenv($key);

        return false !== $envValue ? $envValue : $fallbackValue;
    }
}