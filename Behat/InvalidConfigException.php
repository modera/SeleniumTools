<?php

namespace Modera\Component\SeleniumTools\Behat;

/**
 * @author    Sergei Lissovski <sergei.lissovski@modera.org>
 * @copyright 2017 Modera Foundation
 */
class InvalidConfigException extends \RuntimeException
{
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $message
     * @param string $path
     *
     * @return InvalidConfigException
     */
    public static function create($message, $path)
    {
        $me = new static($message);
        $me->path = $path;

        return $me;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}