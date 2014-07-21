<?php

namespace spec\Ftrrtf\Rollbar\Transport;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AgentSpec extends ObjectBehavior
{
    function let()
    {
        $agentPath = 'path/to/agent';

        $this->beConstructedWith($agentPath);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Transport\Agent');
        $this->shouldImplement('Ftrrtf\Rollbar\Transport\TransportInterface');
    }

    function it_send_items_to_agent()
    {
        $this->send(array());
    }
}
