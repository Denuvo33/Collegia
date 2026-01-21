<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Models\Profile;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\CommentModel;
use App\Models\EventModel;
use App\Models\Collaboration;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = User::withCount(['posts', 'comments', 'events'])
            ->selectRaw('users.*, (SELECT COUNT(*) FROM collaborations WHERE collaborations.user_id = users.id) as collaborations_count')
            ->findOrFail(Auth::id());

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
                'bio' => $user->bio,
                'posts' => $posts->toArray(),
                'comments' => $comments->toArray(),
                'events' => $events,

            ],
            'posts_count' => $user->posts_count,
            'collaborations_count' => $user->collaborations_count ?? 0,
        ]);
    }

    public function posts(Request $request)
    {
        $filter = $request->input('filter', 'all');

        // Get posts
        $posts = Post::where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'hash_id' => $post->hash_id,
                    'type' => 'post',
                    'content' => $post->content,
                    'image' => $post->image,
                    'created_at' => $post->created_at,
                ];
            });

        // Get collaborations and volunteers
        $collaborations = Collaboration::where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($collab) {
                return [
                    'id' => $collab->id,
                    'hash_id' => $collab->hash_id,
                    'type' => $collab->type, // 'collaboration' or 'volunteer'
                    'title' => $collab->title,
                    'content' => $collab->description,
                    'description' => $collab->description,
                    'image' => $collab->image,
                    'location' => $collab->location,
                    'start_date' => $collab->start_date,
                    'end_date' => $collab->end_date,
                    'participants_needed' => $collab->participants_needed,
                    'created_at' => $collab->created_at,
                ];
            });

        // Combine and sort by created_at
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

    public function updateBio(Request $request)
    {
        try {
            $validated = $request->validate([
                'bio' => 'nullable|string|max:500',
            ]);

            // Sanitize HTML to prevent XSS
            if ($validated['bio']) {
                $validated['bio'] = SecurityHelper::sanitizeHtml($validated['bio']);

                // Validate max 20 words (after sanitization)
                $wordCount = str_word_count($validated['bio']);
                if ($wordCount > 20) {
                    return back()->with('error', 'Bio maksimal 20 kata. Saat ini: ' . $wordCount . ' kata.');
                }
            }

            $user = User::findOrFail(Auth::id());
            $user->bio = $validated['bio'];
            $user->save();

            Log::info('Bio updated', [
                'user_id' => Auth::id(),
            ]);

            return back()->with('success', 'Bio berhasil diperbarui.');
        } catch (Exception $e) {
            Log::error('Failed to update bio: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);
            return back()->with('error', 'Gagal memperbarui bio.');
        }
    }

    public function destroy($hashId)
    {
        try {
            // Decode hashId to get real ID
            $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

            if (empty($decoded)) {
                return redirect()->back()->with('error', 'Postingan tidak ditemukan');
            }

            $id = $decoded[0];
            $data = Post::findOrFail($id);

            if ($data->user_id == Auth::id() || Auth::user()->role == 'admin' || Auth::user()->role == 'moderator') {
                $data->delete();

                return redirect()->back()->with('success', 'Postingan telah di hapus');
            } else {
                return redirect()->back()->with('error', 'Gagal menghapus postingan');
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->with('error', 'Postingan tidak ditemukan');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus postingan');
        }
    }
}
