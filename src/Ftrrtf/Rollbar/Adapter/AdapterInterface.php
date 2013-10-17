<?php

namespace Ftrrtf\Rollbar\Adapter;

interface AdapterInterface
{
    public function send($data);
}
