<?php

namespace spec\Ftrrtf\Rollbar\Report;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MessageSpec extends ObjectBehavior
{
    function let()
    {
        $message = 'message';
        $extraData = array();
        $this->beConstructedWith($message, $extraData);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Report\Message');
        $this->shouldImplement('Ftrrtf\Rollbar\Report\ReportInterface');
    }
}
