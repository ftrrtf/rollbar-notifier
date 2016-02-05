<?php

namespace spec\Ftrrtf\Rollbar;

use Ftrrtf\Rollbar\Transport\TransportInterface;
use Ftrrtf\Rollbar\Environment;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

class NotifierSpec extends ObjectBehavior
{

    function let(Environment $environment, TransportInterface $transport)
    {
        $options = array(
            'batched' => false
        );

        $this->beConstructedWith($environment, $transport, $options);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Notifier');
    }

    function it_should_accept_allowed_option()
    {
        $this->setOption('batched', true);
    }

    function it_should_not_accept_invalid_option()
    {
        $this
            ->shouldThrow(UndefinedOptionsException::class)
            ->duringSetOption('invalid_option_name', true);
    }

    function it_gets_environment(Environment $environment)
    {
        $this->getEnvironment()->shouldReturn($environment);
    }

    function it_reports_message()
    {
        $this->reportMessage('message text');
    }

    function it_reports_exception()
    {
        $exception = new \Exception('test exception');
        $this->reportException($exception);
    }

    function it_reports_php_error()
    {
        $errno = $errstr = $errfile = $errline = '';
        $this->reportPhpError($errno, $errstr, $errfile, $errline);
    }

    function it_reports_backtrace_message()
    {
        $this->reportBacktraceMessage(debug_backtrace(), 'message');
    }

    function it_should_be_use_transport_adapter_to_send_message(TransportInterface $transport)
    {
        $transport->send(Argument::any())->shouldBeCalled();

        $this->reportMessage('message text');
    }
}
