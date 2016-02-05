<?php

namespace Ftrrtf\Rollbar;

/**
 * Class LoggerInterface
 *
 * @package Ftrrtf\Rollbar
 */
interface LoggerInterface
{
    public function log($level, $msg);
}
