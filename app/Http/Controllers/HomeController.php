<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\CommentModel;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Post::with('user')->latest()->paginate(10);


        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('user/Home', [
            'data' => $data->toArray()
        ]);
    }


    public function details($hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            abort(404, 'Invalid Post');
        }

        $id = $decoded[0];

        $post = Post::with('user')->findOrFail($id);

        // Get paginated comments for the post
        $comments = CommentModel::with('user')
            ->where('post_id', $id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Merge post data with paginated comments
        $data = $post->toArray();
        $data['comments'] = $comments->toArray();

        return Inertia::render('user/DetailPost', [
            'data' => $data,
        ]);
    }


    public function user(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $user = User::withCount(['posts', 'comments'])->findOrFail($id);

        $posts = Post::where('user_id', $id)->latest()->paginate(10);
        $comments = CommentModel::where('user_id', $id)->latest()->paginate(10);

        return Inertia::render('user/UserProfile', [
            'user' => $user,
            'posts' => $posts->toArray(),
            'comments' => $comments->toArray(),
        ]);
    }

    public function userPosts(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $posts = Post::where('user_id', $id)->latest()->paginate(10);
        return response()->json($posts);
    }

    public function userComments(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $comments = CommentModel::where('user_id', $id)->latest()->paginate(10);
        return response()->json($comments);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(HomeRequest $request)
    {
        try {
            $validated = $request->validated();
            Post::create($validated);

            return redirect()->route('user.home')
                ->with('success', 'Post Berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat post: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(HomeRequest $home)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HomeRequest $home)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HomeRequest $home)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HomeRequest $home)
    {
        //
    }
}
