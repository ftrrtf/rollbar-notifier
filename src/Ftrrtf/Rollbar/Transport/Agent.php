<?php

namespace Ftrrtf\Rollbar\Transport;

class Agent implements TransportInterface
{
    protected $pathToAgent;

    protected $agent = null;

    public function __construct($path)
    {
        $this->pathToAgent = $path;
    }

    public function send($items)
    {
        foreach ($items as $item) {
            fwrite($this->getAgent(), json_encode($item) . "\n");
        }
    }

    protected function getAgent()
    {
        if (is_null($this->agent)) {
            $pathToAgentRelay = $this->pathToAgent . '/rollbar-relay.' . getmypid() . '.' . microtime(true) . '.rollbar';

            $this->agent = fopen($pathToAgentRelay, 'a');
        }

        return $this->agent;
    }
}
