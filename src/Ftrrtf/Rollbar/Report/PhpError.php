<?php

namespace Ftrrtf\Rollbar\Report;

use Ftrrtf\Rollbar\Notifier;

class PhpError extends BaseReport
{
    protected $captureErrorBacktraces;

    static $phpErrors = array(
        E_ERROR => array(
            'constant' => 'E_ERROR',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_WARNING => array(
            'constant' => 'E_WARNING',
            'level' => Notifier::LEVEL_WARNING
        ),
        E_PARSE => array(
            'constant' => 'E_PARSE',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_NOTICE => array(
            'constant' => 'E_NOTICE',
            'level' => Notifier::LEVEL_INFO
        ),
        E_CORE_ERROR => array(
            'constant' => 'E_CORE_ERROR',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_CORE_WARNING => array(
            'constant' => 'E_CORE_WARNING',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_COMPILE_ERROR => array(
            'constant' => 'E_COMPILE_ERROR',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_COMPILE_WARNING => array(
            'constant' => 'E_COMPILE_WARNING',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_USER_ERROR => array(
            'constant' => 'E_USER_ERROR',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_USER_WARNING => array(
            'constant' => 'E_USER_WARNING',
            'level' => Notifier::LEVEL_WARNING
        ),
        E_USER_NOTICE => array(
            'constant' => 'E_USER_NOTICE',
            'level' => Notifier::LEVEL_INFO
        ),
        E_STRICT => array(
            'constant' => 'E_STRICT',
            'level' => Notifier::LEVEL_INFO
        ),
        E_RECOVERABLE_ERROR => array(
            'constant' => 'E_RECOVERABLE_ERROR',
            'level' => Notifier::LEVEL_ERROR
        ),
        E_DEPRECATED => array(
            'constant' => 'E_DEPRECATED',
            'level' => Notifier::LEVEL_INFO
        ),
        E_USER_DEPRECATED => array(
            'constant' => 'E_USER_DEPRECATED',
            'level' => Notifier::LEVEL_INFO
        ),
    );

    public function __construct($errorLevel, $errorMessage, $errorFile, $errorLine)
    {
        // build something that looks like an exception
        $this->report =  array(
            'frames' => $this->buildErrorFrames($errorFile, $errorLine),
            'exception' => array(
                'class' => $this->getPhpConstant($errorLevel),
                'message' => $errorMessage
            )
        );

    }

    public function getType()
    {
        return 'trace';
    }

    protected function buildErrorFrames($errorFile, $errorLine)
    {
        if (!$this->captureErrorBacktraces) {
            return array(
                array(
                    'filename' => $errorFile,
                    'lineno' => $errorLine
                )
            );
        }

        $frames = array();
        $backtrace = debug_backtrace();
        foreach ($backtrace as $frame) {
            // skip frames in this file
            if (isset($frame['file']) && $frame['file'] == __FILE__) {
                continue;
            }
            // skip the confusing set_error_handler frame
            if ($frame['function'] == 'report_php_error' && count($frames) == 0) {
                continue;
            }

            $frames[] = array(
                // Sometimes, file and line are not set. See:
                // http://stackoverflow.com/questions/4581969/why-is-debug-backtrace-not-including-line-number-sometimes
                'filename' => isset($frame['file']) ? $frame['file'] : "<internal>",
                'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                'method' => $frame['function']
            );
        }

        // rollbar expects most recent call last, not first
        $frames = array_reverse($frames);

        // add top-level file and line to end of the reversed array
        $frames[] = array(
            'filename' => $errorFile,
            'lineno' => $errorLine
        );

        $frames = $this->shiftMethod($frames);

        return $frames;
    }

    static public function getErrorLevel($level)
    {
        return self::$phpErrors[$level]['level'];
    }

    static public function getPhpConstant($level)
    {
        return self::$phpErrors[$level]['constant'];
    }
}
