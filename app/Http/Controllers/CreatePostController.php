<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Models\CreatePost;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CreatePostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $auth = Auth::user();
        return Inertia::render('user/CreatePost', [
            'user' => $auth,
        ]);
    }


    public function store(HomeRequest $request)
    {
        try {
            $data = $request->validated();

            // Sanitize content to prevent XSS
            $data['content'] = SecurityHelper::sanitizeHtml($data['content']);

            // Check for spam content
            if (SecurityHelper::containsSpam($data['content'])) {
                return redirect()->back()
                    ->withErrors(['error' => 'Konten terdeteksi sebagai spam. Mohon gunakan kata-kata yang lebih sopan.'])
                    ->withInput();
            }

            // Check for duplicate content (within last 10 minutes)
            if (SecurityHelper::isDuplicate($data['content'], 'post', Auth::id(), 10)) {
                return redirect()->back()
                    ->withErrors(['error' => 'Post dengan konten yang sama sudah dibuat dalam 10 menit terakhir.'])
                    ->withInput();
            }

            $data['user_id'] = Auth::id();

            // Handle image upload with security checks
            if ($request->hasFile('image')) {
                // Additional image safety validation
                if (!SecurityHelper::validateImageSafety($request->file('image'))) {
                    return redirect()->back()
                        ->withErrors(['image' => 'File gambar tidak valid atau berpotensi berbahaya.'])
                        ->withInput();
                }

                $data['image'] = $request->file('image')->storeAs(
                    'images',
                    Str::uuid() . '.' . $request->image->extension(),
                    'public'
                );
            }

            // Store content hash for duplicate detection
            $data['content_hash'] = SecurityHelper::getContentHash($data['content']);

            Post::create($data);

            Log::info('Post created successfully', [
                'user_id' => Auth::id(),
                'content_length' => strlen($data['content']),
            ]);

            return redirect()->route('user.home')->with('success', 'Post created successfully!');
        } catch (\Exception $e) {
            Log::error('Failed to create post: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create post. Please try again.'])
                ->withInput();
        }
    }
}
