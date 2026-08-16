<?php

namespace App\Listeners;

use App\Events\NewPostEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class Log_New_Post_Created
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NewPostEvent $event): void
    {
        // // Log Info
        Log::info('New Post Created title: ' . $event->post->post_title . ', content: ' . $event->post->post_content);
    }
}
