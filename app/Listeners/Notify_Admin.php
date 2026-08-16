<?php

namespace App\Listeners;

use App\Events\NewPostEvent;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class Notify_Admin
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
        // // Notify Admin: one email to all admins at once (single SMTP send),
        // // delayed so it never lands in the same second as the mail above -
        // // Mailtrap's sandbox inbox rejects sends that are too close together.
        $adminEmails = User::where('role', 'admin')->pluck('email')->all();

        if (! empty($adminEmails)) {
            $notification = (new NewPostNotification($event->post))->delay(now()->addSeconds(15));
            Notification::route('mail', $adminEmails)->notify($notification);
        }
    }
}
