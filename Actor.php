<?php

namespace Modera\Component\SeleniumTools;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Modera\Component\SeleniumTools\Exceptions\ActorExecutionException;
use Selenium\Browser;

/**
 * Represents a user and a browser that he is using for testing.
 *
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
     * @var TestHarness
     */
    private $harness;

    /**
     * @var RemoteWebDriver
     */
    private $driver;

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
     * @var ActorBrowserController
     */
    private $controller;

    /**
     * @param string $name A name that represents this user, like "admin". This name later can be used by listeners
     *                      to properly format execution logs and things like that
     * @param string $startUrl A URL of default page that will be opened in a browser
     * @param TestHarness $harness A test harness this actor belongs to
     */
    public function __construct(
        $name, $startUrl, TestHarness $harness
    )
    {
        $this->name = $name;
        $this->startUrl = $startUrl;
        $this->harness = $harness;
    }

    /**
     * @return string
     */
    public function getStartUrl()
    {
        return $this->startUrl;
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
     *
     * @return string[]
     */
    public function enableBehaviour($behaviour)
    {
        if (!in_array($behaviour, $this->enabledBehaviours)) {
            $this->enabledBehaviours[] = $behaviour;
        }

        return $this->enabledBehaviours;
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
     * @internal
     *
     * @return bool
     */
    public function isDriverCreated()
    {
        return null !== $this->driver;
    }

    /**
     * @return RemoteWebDriver
     */
    public function getDriver()
    {
        if (!$this->driver) {
            $this->driver = $this->harness->createDriver($this);
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
        $ctr = $this->getController();

        if (!$this->isDriverCreated() && $ctr->doesBrowserNeedToBeLaunchedAutomatically()) {
            $ctr->launchBrowser($this->startUrl);
        }

        if (!$this->driver) {
            throw new \RuntimeException(sprintf(
                'Session is not yet started for "%s" actor.', $this->name
            ));
        }

        if ($ctr->doesBrowserWindowNeedToBeMaximized()) {
            $ctr->maximizeBrowser();
        }

        if ($ctr->isFocusingNeeded()) {
            $this->focus(1);
        }

        $argumentsFactory = $this->harness->getAdditionalActorArgumentsFactory();
        if ($argumentsFactory && count($this->additionalArguments) == 0) {
            $this->additionalArguments = call_user_func_array(
                $argumentsFactory,
                [$this->getDriver(), $this, $this->harness]
            );
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

    /**
     * @internal
     *
     * @return ActorBrowserController
     */
    protected function getController()
    {
        if (!$this->controller) {
            $this->controller = new ActorBrowserController($this);
        }

        return $this->controller;
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