<?php

namespace App\Listeners;

use App\Events\NewPostEvent;
use App\Mail\NewPostCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class Send_Mail_For_New_Post
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
        // dd($event);
        $response = Mail::to('xyz@gmail.com')->send(new NewPostCreated($event->post));
    }
}