<?php

namespace Modera\Component\SeleniumTools\Behat;

/**
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