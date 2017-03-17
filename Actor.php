<?php

namespace Modera\Component\SeleniumTools;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\Exceptions\ActorExecutionException;
use Selenium\Browser;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class Actor
{
    /**
     * Automatically maximize a browser window whenever a "run" method is invoked.
     */
    const BHR_AUTO_MAXIMIZE = 'auto_maximize';

    /**
     * Automatically focus a window when "run" method is invoked. This is especially useful if you are recording
     * video and several actors are involved in scenario, so you want to see what's is happening in a browser
     * when it is being manipulated.
     */
    const BHR_AUTO_FOCUS = 'auto_focus';

    /**
     * Automatically start browser session on first invocation of "run" method.
     */
    const BHR_AUTO_START = 'auto_start';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $startUrl;

    /**
     * @var array
     */
    private $behaviours;

    /**
     * @var TestHarness
     */
    private $harness;

    /**
     * @var RemoteWebDriver
     */
    private $driver;

    /**
     * @var callable
     */
    private $additionalArgumentsFactory;

    /**
     * Additional cached arguments for callback given in "run" method.
     *
     * @var array
     */
    private $additionalArguments = [];

    /**
     * @var array
     */
    private $enabledBehaviours = [
        self::BHR_AUTO_MAXIMIZE,
        self::BHR_AUTO_FOCUS,
        self::BHR_AUTO_START
    ];

    /**
     * @param string $name  A name that represents this user, like "admin". This name later can be used by listeners
     *                      to properly format execution logs and things like that
     * @param string $startUrl  A URL of default page that will be opened in a browser
     * @param array $behaviours
     * @param TestHarness $harness  A test harness this actor belongs to
     * @param callable|null $additionalArgumentsFactory  Optional callback that can be used to provide additional parameters
     *                                                   for a "callback" argument when "run" method is executed
     */
    public function __construct(
        $name, $startUrl, array $behaviours, TestHarness $harness, callable $additionalArgumentsFactory = null
    )
    {
        $this->name = $name;
        $this->startUrl = $startUrl;
        $this->behaviours = $behaviours;
        $this->harness = $harness;
        $this->additionalArgumentsFactory = $additionalArgumentsFactory;
    }

    /**
     * See BHR_* constants of this class.
     *
     * @param string $behaviour
     *
     * @return bool
     */
    public function isBehaviourEnabled($behaviour)
    {
        return in_array($behaviour, $this->enabledBehaviours);
    }

    /**
     * See BHR_* constants of this class.
     *
     * @param string $behaviour
     */
    public function disableBehaviour($behaviour)
    {
        $filtered = [];
        foreach ($this->enabledBehaviours as $iteratedBehaviour) {
            if ($behaviour != $iteratedBehaviour) {
                $filtered[] = $iteratedBehaviour;
            }
        }
        $this->enabledBehaviours = $filtered;
    }

    /**
     * @see BHR_* constants
     *
     * @param string[] $behaviours
     *
     * @return Actor
     */
    public function setEnabledBehaviours($behaviours)
    {
        $this->enabledBehaviours = $behaviours;

        return $this;
    }

    /**
     * @return TestHarness
     */
    public function getHarness()
    {
        return $this->harness;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return RemoteWebDriver
     */
    public function getDriver()
    {
        if (!$this->driver) {
            $this->driver = $this->createDriver();
        }

        return $this->driver;
    }

    /**
     * @param int $ackDelay
     */
    public function focus($ackDelay = 0)
    {
        $this->getDriver()->executeScript("alert('I am $this->name.');");
        if ($ackDelay > 0) {
            sleep($ackDelay);
        }

        $this->getDriver()->switchTo()->alert()->accept();
    }

    /**
     * @param callable $callback  The callback will always receive at least two arguments: browser and instance of the
     *                            actor itself
     *
     * @return Actor
     */
    public function run(callable $callback)
    {
        if (!$this->driver && $this->isBehaviourEnabled(self::BHR_AUTO_START)) {
            $this->getDriver()->get($this->startUrl);
        }

        if (!$this->driver) {
            throw new \RuntimeException(sprintf(
                'Session is not yet started for "%s" actor.', $this->name
            ));
        }

        if ($this->additionalArgumentsFactory && count($this->additionalArguments) == 0) {
            $this->additionalArguments = call_user_func_array(
                $this->additionalArgumentsFactory,
                [$this->getDriver(), $this, $this->harness]
            );
        }

        if ($this->isBehaviourEnabled(self::BHR_AUTO_MAXIMIZE)) {
            $this->getDriver()->manage()->window()->maximize();
            // it takes about a second for a browser to be maximized and its UI properly redrawn
            sleep(1);
        }
        if ($this->isBehaviourEnabled(self::BHR_AUTO_FOCUS) && $this->isExcessiveFocusingAvoided()) {
            $this->focus(1);
        }

        $args = array_merge([$this->driver, $this], $this->additionalArguments);

        $this->harness->setActiveActor($this);
        try {
            call_user_func_array($callback, $args);
        } catch (\Exception $e) {
            throw new ActorExecutionException(
                sprintf('An error occurred when executing "%s" actor: %s', $this->name, $e->getMessage()), null, $e
            );
        }

        return $this;
    }

    private function isExcessiveFocusingAvoided()
    {
        return !$this->harness->isActorActive($this);
    }

    /**
     * @return RemoteWebDriver
     */
    protected function createDriver()
    {
        $host = $this->resolveConfigValue('SELENIUM_HOST', 'http://localhost:4444/wd/hub');

        try {
            return RemoteWebDriver::create(
                $host,
                $this->behaviours,
                $this->resolveConfigValue('SELENIUM_CONNECTION_TIMEOUT', 30 * 1000),
                $this->resolveConfigValue('SELENIUM_REQUEST_TIMEOUT', 15 * 10000)
            );
        } catch (\Exception $e) {
            throw new ActorExecutionException(
                sprintf('Actor "%s" was unable to establish connection with Selenium using host "%s".', $this->name, $host),
                null,
                $e
            );
        }

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

    /**
     * @return bool
     */
    public function isAlive()
    {
        return null !== $this->driver;
    }

    public function kill()
    {
        if ($this->driver) {
            $this->driver->quit();
            $this->driver = null;
        }
    }
}