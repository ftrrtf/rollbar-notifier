<?php

namespace Ftrrtf\Rollbar\Report;

abstract class BaseReport implements ReportInterface
{
    protected $report;

    abstract function getType();

    public function getReport()
    {
        return $this->report;
    }

    /**
     * Shift 'method' values down one frame, so they reflect where the call
     * occurs (like Rollbar expects), instead of what is being called.
     *
     * @param $frames
     */
    protected function shiftMethod($frames)
    {
        for ($i = count($frames) - 1; $i > 0; $i--) {
            $frames[$i]['method'] = $frames[$i - 1]['method'];
        }
        $frames[0]['method'] = '<main>';

        return $frames;
    }
}
