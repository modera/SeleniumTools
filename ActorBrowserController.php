<?php

namespace Modera\Component\SeleniumTools;

/**
 * @internal
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class ActorBrowserController
{
    /**
     * @var Actor
     */
    private $actor;

    /**
     * @param Actor $actor
     */
    public function __construct(Actor $actor)
    {
        $this->actor = $actor;
    }

    /**
     * @return bool
     */
    public function doesBrowserNeedToBeLaunchedAutomatically()
    {
        return $this->actor->isBehaviourEnabled(Actor::BHR_AUTO_START);
    }

    /**
     * @param string $url
     */
    public function launchBrowser($url)
    {
        $this->actor->getDriver()->get($url);
    }

    /**
     * @return bool
     */
    public function doesBrowserWindowNeedToBeMaximized()
    {
        return $this->actor->isBehaviourEnabled(Actor::BHR_AUTO_MAXIMIZE);
    }

    public function maximizeBrowser()
    {
        $this->actor->getDriver()->manage()->window()->maximize();
        // it takes about a second for a browser to be maximized and its UI properly redrawn
        sleep(1);
    }

    /**
     * @return bool
     */
    public function isFocusingNeeded()
    {
        return $this->actor->isBehaviourEnabled(Actor::BHR_AUTO_FOCUS) && $this->isExcessiveFocusingAvoided();
    }

    private function isExcessiveFocusingAvoided()
    {
        return !$this->actor->getHarness()->isActorActive($this->actor);
    }
}