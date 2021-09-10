<?php

namespace App\Events;

class ThrowException
{
    /**
     * Exception
     *
     * @var Exception|TypeError
     */
    public $exception;

    public function __construct ($exception)
    {
        $this->exception = $exception;
    }
}
