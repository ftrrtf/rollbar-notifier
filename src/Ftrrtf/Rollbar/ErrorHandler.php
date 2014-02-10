<?php

namespace Ftrrtf\Rollbar;

/**
 * Class ErrorHandler
 *
 * @package Ftrrtf\Rollbar
 */
class ErrorHandler
{
    static $registerShutdownFlag = false;

    /**
     * @param Notifier $notifier
     */
    public function registerExceptionHandler(Notifier $notifier)
    {
        set_exception_handler(function($exception) use ($notifier){
            $notifier->reportException($exception);
        });
    }

    /**
     * @param Notifier $notifier
     */
    public function registerErrorHandler(Notifier $notifier)
    {
        set_error_handler(function($errorLevel, $errorMessage, $errorFile, $errorLine) use ($notifier){
            $notifier->reportPhpError($errorLevel, $errorMessage, $errorFile, $errorLine);
        });
    }

    /**
     * @param Notifier $notifier
     */
    public function registerShutdownHandler(Notifier $notifier)
    {
        $self = $this;

        if (!self::$registerShutdownFlag) {
            register_shutdown_function(function() use ($notifier, $self){
                if (false != ($lastError = $self->catchLastError())) {
                    $notifier->reportPhpError($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
                }
                $notifier->flush();
            });
            self::$registerShutdownFlag = true;
        }
    }

    /**
     * @return array|bool
     */
    public function catchLastError()
    {
        // Catch any fatal errors that are causing the shutdown
        $lastError = error_get_last();

        if (!is_null($lastError)) {
            switch($lastError['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:
                case E_RECOVERABLE_ERROR:
                    return $lastError;
                    break;
            }
        }

        return false;
    }
}