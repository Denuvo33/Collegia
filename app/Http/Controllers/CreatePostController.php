<?php

namespace App\Http\Controllers;

use App\Models\CreatePost;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CreatePostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $auth = Auth::user();
        return Inertia::render('user/CreatePost',[
            'user' => $auth,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(HomeRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('images', 'public');
            }

            Post::create($data);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create post: ' . $e->getMessage()]);
        }
        return redirect()->route('user.home')->with('success', 'Post created successfully!');
    }

}
