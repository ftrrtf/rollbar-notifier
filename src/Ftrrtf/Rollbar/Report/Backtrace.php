<?php

namespace Ftrrtf\Rollbar\Report;

class Backtrace extends BaseReport
{
    /**
     * @param $message
     * @param $backtrace
     * @param $extraData
     */
    public function __construct($backtrace, $message, $extraData)
    {
        $this->report = array(
            'frames' => $this->buildBacktraceFrames($backtrace),
            'exception' => array(
                'class' => 'backtrace',
                'message' => $message
            )
        );

//        if ($extraData !== null && is_array($extraData)) {
//            $data['custom'] = $extraData;
//        }
    }

    public function getType()
    {
        return 'trace';
    }


    /**
     * @param array $backtrace
     * @return array
     */
    private function buildBacktraceFrames($backtrace)
    {
        $frames = array();
        foreach ($backtrace as $frame) {
            $frames[] = array(
                'filename' => isset($frame['file'])
                    ? $frame['file']
                    : (isset($frame['class'])
                        ? '<Class> ' . $frame['class']
                        : '<internal>'),
                'lineno' =>  isset($frame['line']) ? $frame['line'] : 0,
                'method' => $frame['function'],
                // TODO include args? need to sanitize first.
            );
        }

        // rollbar expects most recent call to be last, not first
        $frames = array_reverse($frames);

        return $frames;
    }
}
