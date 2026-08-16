<?php

namespace App\Http\Controllers;

use App\Mail\NewPostCreated;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            //
            Log::info('New Post Created title: '.$post->post_title.', content: '.$post->post_content);
            dd($response);
        }

        return redirect()->back()->with('message', 'Data Inserted Successfully!');
    }
}
