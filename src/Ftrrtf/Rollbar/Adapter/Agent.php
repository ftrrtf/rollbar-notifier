<?php

namespace Ftrrtf\Rollbar\Adapter;

class Agent implements AdapterInterface
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
            fwrite($this->agentLog, json_encode($item) . "\n");
        }
    }

    protected function getAgent()
    {
        if (is_null($this->agent)) {
            $pathToAgentRelay = $this->pathToAgent . '/rollbar-relay.' . getmypid() . '.rollbar';
            if (!file_exists($pathToAgentRelay)) {
                throw new AgentNotFoundException();
            }

            $this->agent = fopen($pathToAgentRelay, 'a');
        }

        return $this->agent;
    }
}
