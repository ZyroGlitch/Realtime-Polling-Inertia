<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{

    public function posts(){
        $posts = Post::all();
        return inertia('dashboard',compact('posts'));
    }

    public function myPosts(){
        $posts = Post::latest()->get();
        return inertia('Posts/My_Post',compact('posts'));
    }

    public function store(Request $request)
    {
        // dd($request);
        $data = $request->validate([
            'post_title' => 'required|string',
            'post_content' => 'required|string',
        ]);

        Post::create($data);
        return redirect()->back()->with('message', 'Data Inserted Successfully!');
    }
}
