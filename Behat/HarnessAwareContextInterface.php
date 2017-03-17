<?php

namespace Modera\Component\SeleniumTools\Behat;

/**
 * Behat Context, meant to be used in conjunction with \Behat\Behat\Context\Context interface.
 *
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
interface HarnessAwareContextInterface
{
    /**
     * @param TestHarnessFactory $harnessFactory
     */
    public function acceptHarnessFactory(TestHarnessFactory $harnessFactory);
}