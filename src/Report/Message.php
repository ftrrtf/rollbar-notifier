<?php

namespace Ftrrtf\Rollbar\Report;

class Message extends BaseReport
{
    function __construct($message, $extraData)
    {
        $report = array('body' => $message);

        if ($extraData !== null && is_array($extraData)) {
            // merge keys from $extra_data to $message_obj
            foreach ($extraData as $key => $val) {
                if ($key == 'body') {
                    // rename to 'body_' to avoid clobbering
                    $key = 'body_';
                }
                $report[$key] = $val;
            }
        }

        $this->report = $report;
    }

    public function getType()
    {
        return 'message';
    }
}
