<?php

namespace Ftrrtf\Rollbar\Transport;

interface TransportInterface
{
    public function send($data);
}
