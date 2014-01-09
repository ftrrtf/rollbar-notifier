<?php

namespace spec\Ftrrtf\Rollbar\Report;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PhpErrorSpec extends ObjectBehavior
{
    public function let()
    {
        $errorLevel = E_NOTICE;
        $errorMessage = "Some notice message";
        $errorFile = 'file.php';
        $errorLine = '10';
        $this->beConstructedWith($errorLevel, $errorMessage, $errorFile, $errorLine);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Ftrrtf\Rollbar\Report\PhpError');
        $this->shouldImplement('Ftrrtf\Rollbar\Report\ReportInterface');
    }

    public function it_get_rollbar_error_level_by_php_error_level()
    {
        $this->getErrorLevel(E_NOTICE)->shouldReturn('info');
        $this->getErrorLevel(E_ERROR)->shouldReturn('error');
        $this->getErrorLevel(E_DEPRECATED)->shouldReturn('info');
    }

    public function it_get_php_error_constant_name_by_php_error_level()
    {
        $this->getPhpConstant(E_NOTICE)->shouldReturn('E_NOTICE');
        $this->getPhpConstant(E_ERROR)->shouldReturn('E_ERROR');
        $this->getPhpConstant(E_DEPRECATED)->shouldReturn('E_DEPRECATED');
    }
}
