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
                        'image_url' => $event->image_url,
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
                'image_url' => $post->image_url,
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
                'image_url' => $collab->image_url,
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

        $user = User::withCount(['posts', 'comments'])
            ->selectRaw('users.*, (SELECT COUNT(*) FROM collaborations WHERE collaborations.user_id = users.id) as collaborations_count')
            ->selectRaw('(SELECT COUNT(*) FROM event WHERE event.user_id = users.id AND event.is_visible = 1) as events_count')
            ->findOrFail($id);

        // Load first page of all tabs for better UX and performance
        $posts = Post::where('user_id', $id)->latest()->paginate(10);
        $comments = CommentModel::where('user_id', $id)->latest()->paginate(10);
        $events = EventModel::where('user_id', $id)
            ->where('is_visible', true)
            ->latest()
            ->paginate(10);

        return Inertia::render('user/UserProfile', [
            'user' => [
                'id' => $user->id,
                'hash_id' => $user->hash_id,
                'name' => $user->name,
                'clean_name' => $user->clean_name,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'posts_count' => $user->posts_count,
                'comments_count' => $user->comments_count,
                'events_count' => $user->events_count,
                'collaboration_interests_count' => \App\Models\CollaborationInterest::where('user_id', $id)->count(),
                'private_post' => $user->private_post,
                'private_comment' => $user->private_comment,
                'bio' => $user->bio,
            ],
            'posts' => $posts->toArray(),
            'comments' => $comments->toArray(),
            'events' => $events->toArray(),
        ]);
    }

    public function userPosts(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $filter = $request->input('filter', 'all');

        // Get posts
        $posts = Post::where('user_id', $id)
            ->latest()
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'hash_id' => $post->hash_id,
                    'type' => 'post',
                    'content' => $post->content,
                    'image' => $post->image,
                    'image_url' => $post->image_url,
                    'created_at' => $post->created_at,
                ];
            });

        // Get collaborations and volunteers
        $collaborations = Collaboration::where('user_id', $id)
            ->latest()
            ->get()
            ->map(function ($collab) {
                return [
                    'id' => $collab->id,
                    'hash_id' => $collab->hash_id,
                    'type' => $collab->type,
                    'title' => $collab->title,
                    'content' => $collab->description,
                    'description' => $collab->description,
                    'image' => $collab->image,
                    'image_url' => $collab->image_url,
                    'location' => $collab->location,
                    'start_date' => $collab->start_date,
                    'end_date' => $collab->end_date,
                    'participants_needed' => $collab->participants_needed,
                    'created_at' => $collab->created_at,
                ];
            });

        // Combine and sort
        $combined = $posts->concat($collaborations)
            ->sortByDesc('created_at')
            ->values();

        // Filter by type if not 'all'
        if ($filter !== 'all') {
            $combined = $combined->filter(function ($item) use ($filter) {
                return $item['type'] === $filter;
            })->values();
        }

        // Paginate manually
        $page = $request->input('page', 1);
        $perPage = 10;
        $total = $combined->count();
        $items = $combined->forPage($page, $perPage)->values();

        return response()->json([
            'data' => $items,
            'current_page' => (int) $page,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function userComments(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $comments = CommentModel::where('user_id', $id)->latest()->paginate(10);
        return response()->json($comments);
    }

    public function userEvents(Request $request, $hashId)
    {
        $id = Hashids::decode($hashId)[0] ?? null;
        if (!$id) abort(404);

        $events = EventModel::where('user_id', $id)
            ->where('is_visible', true)
            ->latest()
            ->paginate(10);

        return response()->json($events);
    }
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
}
