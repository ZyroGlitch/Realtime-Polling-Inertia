<?php

namespace App\Providers;

use App\Events\NewPostEvent;
use App\Listeners\Log_New_Post_Created;
use App\Listeners\Notify_Admin;
use App\Listeners\Send_Mail_For_New_Post;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(NewPostEvent::class, Send_Mail_For_New_Post::class);
        Event::listen(NewPostEvent::class, Notify_Admin::class);
        Event::listen(NewPostEvent::class, Log_New_Post_Created::class);
    }
}
