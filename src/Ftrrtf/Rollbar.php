<?php

namespace Ftrrtf;

use Ftrrtf\Rollbar\Environment;

/**
 * Singleton-style wrapper around RollbarNotifier
 *
 * Unless you need multiple RollbarNotifier instances in the same project, use this.
 */
class Rollbar
{
    /** @var Rollbar\Notifier */
    public static $instance = null;

    public static function init($config, $set_exception_handler = true, $set_error_handler = true)
    {
        $environment = new Environment(array());
        self::$instance = new Rollbar\Notifier($environment, $config);
        self::$instance->setAdapter(new Rollbar\Adapter\Curl());

        if ($set_exception_handler) {
            set_exception_handler('\Ftrrtf\Rollbar::reportException');
        }
        if ($set_error_handler) {
            set_error_handler('\Ftrrtf\Rollbar::reportPhpError');
        }

//        if (self::$instance->batched) {
            register_shutdown_function('\Ftrrtf\Rollbar::flush');
//        }

        return self::$instance;
    }

    public static function reportException($exc)
    {
        if (self::$instance == null) {
            return;
        }
        return self::$instance->reportException($exc);
    }

    public static function reportMessage($message, $level = 'error', $extraData = null)
    {
        if (self::$instance == null) {
            return;
        }
        return self::$instance->reportMessage($message, $level, $extraData);
    }

    public static function reportBacktraceMessage($backtrace, $message, $level = 'error', $extraData = null) {
        if (self::$instance == null) {
            return;
        }
        return self::$instance->reportBacktraceMessage($backtrace, $message, $level, $extraData);
    }

    public static function reportPhpError($errno, $errstr, $errfile, $errline) {
        if (self::$instance != null) {
            return self::$instance->reportPhpError($errno, $errstr, $errfile, $errline);
        }
        return false;
    }

    public static function flush() {
        // Catch any fatal errors that are causing the shutdown
        $last_error = error_get_last();
        if (!is_null($last_error)) {
            switch($last_error['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:
                    self::$instance->reportPhpError($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
                    break;
            }
        }
        self::$instance->flush();
    }
}
