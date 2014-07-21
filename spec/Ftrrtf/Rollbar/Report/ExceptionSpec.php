<?php

namespace spec\Ftrrtf\Rollbar\Report;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ExceptionSpec extends ObjectBehavior
{
    function let()
    {
        $exception = new \Exception('test exception');
        $this->beConstructedWith($exception);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Report\Exception');
        $this->shouldImplement('Ftrrtf\Rollbar\Report\ReportInterface');
    }
}
