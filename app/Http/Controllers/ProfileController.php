<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\CommentModel;
use App\Models\EventModel;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::withCount(['posts', 'comments', 'events'])->findOrFail(Auth::id());

        // Load events with transaction data and is_promoted calculation
        $today = now()->toDateString();
        $events = EventModel::with('transaction')
            ->select('*')
            ->selectRaw("
                CASE 
                    WHEN promoted_till IS NOT NULL 
                         AND DATE(promoted_till) >= ? 
                    THEN 1 
                    ELSE 0 
                END AS is_promoted
            ", [$today])
            ->where('user_id', Auth::id())
            ->orderByDesc('is_promoted')
            ->latest()
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'hash_id' => $event->hash_id,
                    'title' => $event->title,
                    'content' => $event->content,
                    'date_event' => $event->date_event,
                    'location' => $event->location,
                    'price' => $event->price,
                    'image' => $event->image,
                    'is_visible' => $event->is_visible,
                    'is_promoted' => (bool) $event->is_promoted,
                    'promoted_till' => $event->promoted_till,
                    'created_at' => $event->created_at,
                    'order_id' => $event->transaction ? $event->transaction->order_id : null,
                    'transaction_status' => $event->transaction ? $event->transaction->transaction_status : null,
                ];
            });

        $posts = Post::where('user_id', Auth::id())->latest()->paginate(10);
        $comments = CommentModel::where('user_id', Auth::id())->latest()->paginate(10);

        return Inertia::render('user/Profile', [
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'clean_name' => $user->clean_name,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'private_post' => $user->private_post,
                'posts' => $posts->toArray(),
                'comments' => $comments->toArray(),
                'events' => $events,

            ],
            'posts_count' => $user->posts_count,
        ]);
    }

    public function posts(Request $request)
    {
        $posts = Post::where('user_id', Auth::id())->latest()->paginate(10);
        return response()->json($posts);
    }

    public function comments(Request $request)
    {
        $comments = CommentModel::where('user_id', Auth::id())->latest()->paginate(10);
        return response()->json($comments);
    }

    public function updatePrivacy(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);


            $request->validate([
                'private_post' => 'nullable|in:0,1',
                'private_comment' => 'nullable|in:0,1',
            ]);

            $user->update($request->only(['private_post', 'private_comment']));

            return back()->with('success', 'Privacy settings updated.');
        } catch (Exception $e) {
            return back()->with('error', 'An error occurred while updating privacy settings.');
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Profile $profile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Profile $profile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Profile $profile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $data = Post::findOrFail($id);
            if ($data->user_id == Auth::id() || Auth::user()->role == 'admin' || Auth::user()->role == 'moderator') {
                $data->delete();

                return redirect()->back()->with('success', 'Postingan telah di hapus');
            } else {
                return redirect()->back()->with('error', 'Gagal menghapus postingan');
            }
        } catch (Exception $e) {
        }
    }
}
