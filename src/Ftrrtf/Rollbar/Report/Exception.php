<?php

namespace Ftrrtf\Rollbar\Report;

class Exception extends BaseReport
{
    public function __construct($exception)
    {
        $this->report = array(
            'frames' => $this->buildExceptionFrames($exception),
            'exception' => array(
                'class' => get_class($exception),
                'message' => $exception->getMessage()
            )
        );
    }

    public function getType()
    {
        return 'trace';
    }

    /**
     * @param $exc \Exception
     * @return array
     */
    protected function buildExceptionFrames($exc)
    {
        $frames = array();
        foreach ($exc->getTrace() as $frame) {
            $frames[] = array(
                'filename' => isset($frame['file']) ? $frame['file'] : '<internal>',
                'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                'method' => $frame['function']
                // TODO include args? need to sanitize first.
            );
        }

        // rollbar expects most recent call to be last, not first
        $frames = array_reverse($frames);

        // add top-level file and line to end of the reversed array
        $frames[] = array(
            'filename' => $exc->getFile(),
            'lineno' => $exc->getLine()
        );

        $frames = $this->shiftMethod($frames);

        return $frames;
    }
}
