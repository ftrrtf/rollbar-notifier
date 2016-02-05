<?php

namespace Ftrrtf\Rollbar\Report;

interface ReportInterface
{
    public function getType();
    public function getReport();
}