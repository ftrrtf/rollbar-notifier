<?php

namespace Ftrrtf\Rollbar\Transport;

class Agent implements TransportInterface
{
    protected $logLocation;

    protected $agentLog = null;

    public function __construct($path)
    {
        $this->logLocation = $path;
    }

    public function send($items)
    {
        foreach ($items as $item) {
            fwrite($this->getAgentLog(), json_encode($item) . "\n");
        }
    }

    protected function getAgentLog()
    {
        if (is_null($this->agentLog)) {
            $pathToAgentRelay = $this->logLocation . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';

            $this->agentLog = fopen($pathToAgentRelay, 'a');
        }

        return $this->agentLog;
    }
}
