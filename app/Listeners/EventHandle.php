<?php

namespace App\Listeners;

use App\Events\ThrowException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\InteractsWithQueue;

class EventHandle
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
    public function handle($event)
    {
        //
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(ThrowException::class, 'App\Listeners\HandleException@handle');
    }
}
