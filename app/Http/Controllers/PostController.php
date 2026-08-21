<?php

namespace App\Http\Controllers;

use App\Events\NewPostEvent;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

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

            // Dispatch the event
            Event::dispatch(new NewPostEvent($post));

            return back()->with('success', 'User New Post Successfully!');
        }

        return redirect()->back()->with('message', 'Data Inserted Successfully!');
    }
}
