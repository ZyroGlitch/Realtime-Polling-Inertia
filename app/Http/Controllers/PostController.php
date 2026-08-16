<?php

namespace App\Http\Controllers;

use App\Events\NewPostEvent;
use App\Mail\NewPostCreated;
use App\Models\Post;
use App\Models\User;
use App\Notifications\NewPostNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class PostController extends Controller
{
    public function posts()
    {
        $posts = Post::all();

        return inertia('dashboard', compact('posts'));
    }

    public function myPosts()
    {
        $posts = Post::latest()->get();

        return inertia('Posts/My_Post', ['my_posts' => $posts]);
    }

    public function store(Request $request)
    {
        // dd($request);
        $data = $request->validate([
            'post_title' => 'required|string',
            'post_content' => 'required|string',
        ]);

        $post = Post::create($data);

        if ($post) {
            $response = Mail::to('xyz@gmail.com')->send(new NewPostCreated($post));


            // Log Info
            Log::info('New Post Created title: '.$post->post_title.', content: '.$post->post_content);


            // Notify Admin: one email to all admins at once (single SMTP send),
            // delayed so it never lands in the same second as the mail above -
            // Mailtrap's sandbox inbox rejects sends that are too close together.
            $adminEmails = User::where('role', 'admin')->pluck('email')->all();

            if (! empty($adminEmails)) {
                $notification = (new NewPostNotification($post))->delay(now()->addSeconds(15));
                Notification::route('mail', $adminEmails)->notify($notification);
            }


            // Dispatch the event
            Event::dispatch(new NewPostEvent($post));

            // dd($response);
        }

        return redirect()->back()->with('message', 'Data Inserted Successfully!');
    }
}