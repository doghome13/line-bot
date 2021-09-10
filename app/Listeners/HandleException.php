<?php

namespace App\Listeners;

use App\Events\ThrowException;
use App\Models\LogException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HandleException
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ThrowException $event)
    {
        $exception = $event->exception;
        $message   = $exception->getMessage();

        if (is_a($exception, NotFoundHttpException::class)) {
            $message    = 'Not Found ';
        } elseif (is_a($exception, MethodNotAllowedHttpException::class)) {
            $message    = 'Method Not Allowed';
        }

        $log             = new LogException();
        $log->code       = $exception->getCode();
        $log->class_name = get_class($exception);
        $log->file       = $exception->getFile();
        $log->line       = $exception->getLine();
        $log->url        = request()->url();
        $log->message    = $message ?: 'unknown error';
        $log->ip         = request()->ip();
        $log->save();
    }
}
