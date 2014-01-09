<?php

namespace Ftrrtf\Rollbar\Report;

class PhpError extends BaseReport
{
    protected $captureErrorBacktraces;

    static $phpErrors = array(
        1 => array(
            'constant' => 'E_ERROR',
            'level' => 'error'
        ),
        2 => array(
            'constant' => 'E_WARNING',
            'level' => 'warning'
        ),
        8 => array(
            'constant' => 'E_NOTICE',
            'level' => 'info'
        ),
        256 => array(
            'constant' => 'E_USER_ERROR',
            'level' => 'error'
        ),
        512 => array(
            'constant' => 'E_USER_WARNING',
            'level' => 'warning'
        ),
        1024 => array(
            'constant' => 'E_USER_NOTICE',
            'level' => 'info'
        ),
        2048 => array(
            'constant' => 'E_STRICT',
            'level' => 'info'
        ),
        4086 => array(
            'constant' => 'E_RECOVERABLE_ERROR',
            'level' => 'error'
        ),
        8192 => array(
            'constant' => 'E_DEPRECATED',
            'level' => 'info'
        ),
        16384 => array(
            'constant' => 'E_USER_DEPRECATED',
            'level' => 'info'
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
