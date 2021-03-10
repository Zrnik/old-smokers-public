<?php

namespace App\Presenters;

class Error4xxTemplate extends BaseTemplate
{
    public int $code;
    public string $reason;
}
