<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Models\CommentModel;
use App\Models\Notification;
use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Get paginated comments for a specific post
     */
    public function getComments(Request $request, $postId)
    {
        $comments = CommentModel::with('user')
            ->where('post_id', $postId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($comments);
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
    public function store(CommentRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();

            // Sanitize content to prevent XSS
            $data['content'] = SecurityHelper::sanitizeHtml($data['content']);

            // Check for spam
            if (SecurityHelper::containsSpam($data['content'])) {
                return back()
                    ->withErrors(['error' => 'Komentar terdeteksi sebagai spam. Mohon gunakan kata-kata yang lebih sopan.'])
                    ->withInput();
            }

            // Check for duplicates (5 minute window, per user+post)
            $commentHash = SecurityHelper::getContentHash($data['content'] . $data['post_id']);
            $isDuplicate = CommentModel::where('user_id', Auth::id())
                ->where('post_id', $data['post_id'])
                ->where('comment_hash', $commentHash)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->exists();

            if ($isDuplicate) {
                return back()
                    ->withErrors(['error' => 'Komentar yang sama sudah dibuat dalam 5 menit terakhir.'])
                    ->withInput();
            }

            $data['comment_hash'] = $commentHash;
            $comment = CommentModel::create($data);

            $post = Post::findOrFail($data['post_id']);

            Log::info('Comment created successfully', [
                'user_id' => Auth::id(),
                'post_id' => $data['post_id'],
            ]);

            if (Auth::user()->id == $post->user_id) {
                // Return the comment with user data
                return back()->with('newComment', $comment->load('user'));
            }

            Notification::create([
                'message' => Auth::user()->role == 'community' ? Auth::user()->name . ' commented on your post: ' . $request->content : Auth::user()->clean_name . ' commented on your post: ' . $request->content,
                'post_id' => $post->id,
                'user_id' => $post->user_id,
                'is_read' => 0,
            ]);

            return back()->with('success', 'Komentar berhasil dibuat');
        } catch (Exception $e) {
            Log::error('Failed to create comment: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['error' => 'Gagal membuat komentar. Silakan coba lagi.']);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $hashId)
    {
        try {
            // Decode hashId to get real ID
            $decoded = Hashids::decode($hashId);

            if (empty($decoded)) {
                return redirect()->back()->with('error', 'Komentar tidak ditemukan');
            }

            $id = $decoded[0];
            $comment = CommentModel::findOrFail($id);

            // Authorization check
            if ($comment->user_id == Auth::id() || Auth::user()->role == 'admin' || Auth::user()->role == 'moderator') {
                $comment->delete();

                Log::info('Comment deleted successfully', [
                    'comment_id' => $id,
                    'user_id' => Auth::id(),
                ]);

                return redirect()->back()->with('success', 'Komentar berhasil dihapus.');
            }

            return redirect()->back()->withErrors([
                'error' => 'Anda tidak memiliki izin untuk menghapus komentar ini.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->back()->withErrors([
                'error' => 'Komentar tidak ditemukan.'
            ]);
        } catch (Exception $e) {
            Log::error('Failed to delete comment: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->withErrors([
                'error' => 'Terjadi kesalahan, coba lagi nanti.'
            ]);
        }
    }
}
