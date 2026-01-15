<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Post;
use App\Models\User;
use App\Models\Collaboration;
use App\Models\EventModel;
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
        $page = $request->input('page', 1);
        $perPage = 10;

        // Get 2 pinned events (latest visible) - only on first page
        $pinnedEvents = [];
        if ($page == 1) {
            $pinnedEvents = EventModel::with('user')
                ->where('is_visible', true)
                ->latest()
                ->limit(2)
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => $event->id,
                        'type' => 'event',
                        'title' => $event->title,
                        'content' => $event->content,
                        'image' => $event->image,
                        'created_at' => $event->created_at,
                        'hash_id' => $event->hash_id,
                        'user' => $event->user,
                        'date_event' => $event->date_event,
                        'location' => $event->location,
                        'price' => $event->price,
                        'organizer' => $event->organizer,
                    ];
                })->toArray();
        }

        // Get posts
        $posts = Post::with('user')->latest()->get()->map(function ($post) {
            return [
                'id' => $post->id,
                'type' => 'post',
                'content' => $post->content,
                'image' => $post->image,
                'created_at' => $post->created_at,
                'hash_id' => $post->hash_id,
                'user' => $post->user,
            ];
        });

        // Get collaborations
        $collaborations = Collaboration::with('user')->withCount('interests')->latest()->get()->map(function ($collab) {
            return [
                'id' => $collab->id,
                'type' => $collab->type === 'collaboration' ? 'collaboration' : 'volunteer',
                'title' => $collab->title,
                'description' => $collab->description,
                'image' => $collab->image,
                'created_at' => $collab->created_at,
                'hash_id' => $collab->hash_id,
                'user' => $collab->user,
                'location' => $collab->location,
                'participants_needed' => $collab->participants_needed,
                'interests_count' => $collab->interests_count,
            ];
        });

        // Combine and sort by created_at
        $allFeed = collect()
            ->merge($posts)
            ->merge($collaborations)
            ->sortByDesc('created_at')
            ->values();

        // Paginate
        $total = $allFeed->count();
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $feed = $allFeed->slice($offset, $perPage)->values();

        $paginatedData = [
            'data' => $feed->toArray(),
            'current_page' => (int) $page,
            'last_page' => (int) $lastPage,
            'per_page' => $perPage,
            'total' => $total,
            'next_page_url' => $page < $lastPage ? url('/user/home?page=' . ($page + 1)) : null,
        ];

        if ($request->wantsJson()) {
            return response()->json($paginatedData);
        }

        return Inertia::render('user/Home', [
            'pinnedEvents' => $pinnedEvents,
            'feed' => $paginatedData,
        ]);
    }


    public function details($hashId)
    {
        $decoded = Hashids::decode($hashId);

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
