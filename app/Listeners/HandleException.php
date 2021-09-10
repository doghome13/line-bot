<?php

namespace App\Listeners;

use App\Events\ThrowException;
use App\Models\LogException;
use App\Services\Dev\LogService;
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
        $message = $message ?: 'unknown error';

        (new LogService(LogService::LOG_TYPE_EXCEPTION))->add($message, null, $exception);
    }
}
