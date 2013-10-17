<?php

namespace spec\Ftrrtf\Rollbar\Report;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BacktraceSpec extends ObjectBehavior
{
    public function let()
    {
        $message = 'message';
        $backtrace = array();
        $extraData = array();
        $this->beConstructedWith($backtrace, $message, $extraData);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Report\Backtrace');
        $this->shouldImplement('Ftrrtf\Rollbar\Report\ReportInterface');
    }
}
