<?php

namespace spec\Ftrrtf\Rollbar;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NotifierSpec extends ObjectBehavior
{

    /**
     * @param \Ftrrtf\Rollbar\Environment $environment
     * @param \Ftrrtf\Rollbar\Adapter\AdapterInterface $adapter
     */
    public function let($environment, $adapter)
    {
        $options = array(
            'access_token' => 'token',
            'batched' => false
        );

        $this->beConstructedWith($environment, $options);
        $this->setAdapter($adapter);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Notifier');
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

    /**
     * @param \Ftrrtf\Rollbar\Adapter\AdapterInterface $adapter
     */
    public function it_should_be_use_adapter_to_send_message($adapter)
    {
        $adapter->send(Argument::any())->shouldBeCalled();

        $this->reportMessage('message text');
    }
}
