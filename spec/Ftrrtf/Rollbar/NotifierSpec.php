<?php

namespace spec\Ftrrtf\Rollbar;

use Ftrrtf\Rollbar\Transport\TransportInterface;
use Ftrrtf\Rollbar\Environment;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotifierSpec extends ObjectBehavior
{

    public function let(Environment $environment, TransportInterface $transport)
    {
        $options = array(
            'access_token' => 'token',
            'batched' => false
        );

        $this->beConstructedWith($environment, $options);
        $this->setTransport($transport);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Notifier');
    }

    public function it_is_set_allowed_option()
    {
        $this->setOption('batched', true);
    }

    public function it_is_set_not_allowed_option()
    {
        $this
            ->shouldThrow('Symfony\Component\OptionsResolver\Exception\InvalidOptionsException')
            ->duringSetOption('custom', true);
    }

    public function it_get_environment(Environment $environment)
    {
        $this->getEnvironment()->shouldReturn($environment);
    }

    public function it_report_message()
    {
        $this->reportMessage('message text');
    }

    public function it_report_exception()
    {
        $exception = new \Exception('test exception');
        $this->reportException($exception);
    }

    public function it_report_php_error()
    {
        $errno = $errstr = $errfile = $errline = '';
        $this->reportPhpError($errno, $errstr, $errfile, $errline);
    }

    public function it_report_backtrace_message()
    {
        $this->reportBacktraceMessage(debug_backtrace(), 'message');
    }

    public function it_should_be_use_adapter_to_send_message(TransportInterface $transport)
    {
        $transport->send(Argument::any())->shouldBeCalled();

        $this->reportMessage('message text');
    }
}