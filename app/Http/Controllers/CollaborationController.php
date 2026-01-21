<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CollaborationController extends Controller
{
    public function index()
    {
        $auth = Auth::user();
        return Inertia::render('user/CreateCollaboration', [
            'user' => $auth,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:20|max:5000',
            'type' => 'required|in:collaboration,volunteer',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120|dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            'location' => 'nullable|string|min:3|max:255',
            'participants_needed' => 'nullable|integer|min:1|max:1000',
        ]);

        try {
            // Sanitize text fields to prevent XSS
            $validated['title'] = SecurityHelper::sanitizeHtml($validated['title']);
            $validated['description'] = SecurityHelper::sanitizeHtml($validated['description']);
            if (isset($validated['location'])) {
                $validated['location'] = SecurityHelper::sanitizeHtml($validated['location']);
            }

            // Check for spam
            if (
                SecurityHelper::containsSpam($validated['title']) ||
                SecurityHelper::containsSpam($validated['description'])
            ) {
                return redirect()->back()
                    ->withErrors(['error' => 'Konten terdeteksi sebagai spam. Mohon gunakan kata-kata yang lebih sopan.'])
                    ->withInput();
            }

            $validated['user_id'] = Auth::id();

            // Handle image upload with validation
            if ($request->hasFile('image')) {
                if (!SecurityHelper::validateImageSafety($request->file('image'))) {
                    return redirect()->back()
                        ->withErrors(['image' => 'File gambar tidak valid atau berbahaya']);
                }
                $validated['image'] = $request->file('image')->store('collaborations', 'public');
            }

            Collaboration::create($validated);

            Log::info('Collaboration created', [
                'user_id' => Auth::id(),
                'type' => $validated['type'],
            ]);

            return redirect()->route('user.home')->with('success', 'Kolaborasi berhasil dibuat!');
        } catch (\Exception $e) {
            Log::error('Failed to create collaboration: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
            ]);
            return redirect()->back()->withErrors(['error' => 'Gagal membuat kolaborasi']);
        }
    }

    public function show($hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            abort(404, 'Kolaborasi tidak ditemukan');
        }

        $id = $decoded[0];
        $collaboration = Collaboration::with(['user', 'interestedUsers'])->withCount('interests')->find($id);

        if (!$collaboration) {
            abort(404, 'Kolaborasi tidak ditemukan');
        }

        $isInterested = Auth::check() ? $collaboration->interestedUsers->contains(Auth::id()) : false;

        return Inertia::render('user/CollaborationDetail', [
            'collaboration' => $collaboration,
            'isInterested' => $isInterested,
        ]);
    }

    public function edit($hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            abort(404, 'Kolaborasi tidak ditemukan');
        }

        $id = $decoded[0];
        $collaboration = Collaboration::findOrFail($id);

        if ($collaboration->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('user/EditCollaboration', [
            'collaboration' => $collaboration,
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, $hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            abort(404, 'Kolaborasi tidak ditemukan');
        }

        $id = $decoded[0];
        $collaboration = Collaboration::findOrFail($id);

        if ($collaboration->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'required|string|min:5|max:255',
            'description' => 'required|string|min:20|max:5000',
            'type' => 'required|in:collaboration,volunteer',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120|dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            'location' => 'nullable|string|min:3|max:255',
            'participants_needed' => 'nullable|integer|min:1|max:1000',
        ]);

        try {
            // Sanitize text fields
            $validated['title'] = SecurityHelper::sanitizeHtml($validated['title']);
            $validated['description'] = SecurityHelper::sanitizeHtml($validated['description']);
            if (isset($validated['location'])) {
                $validated['location'] = SecurityHelper::sanitizeHtml($validated['location']);
            }

            // Check for spam
            if (
                SecurityHelper::containsSpam($validated['title']) ||
                SecurityHelper::containsSpam($validated['description'])
            ) {
                return redirect()->back()
                    ->withErrors(['error' => 'Konten terdeteksi sebagai spam'])
                    ->withInput();
            }

            if ($request->hasFile('image')) {
                // Validate image safety
                if (!SecurityHelper::validateImageSafety($request->file('image'))) {
                    return redirect()->back()
                        ->withErrors(['image' => 'File gambar tidak valid atau berbahaya']);
                }

                // Delete old image if exists
                if ($collaboration->image) {
                    Storage::disk('public')->delete($collaboration->image);
                }
                $validated['image'] = $request->file('image')->store('collaborations', 'public');
            }

            $collaboration->update($validated);

            Log::info('Collaboration updated', [
                'user_id' => Auth::id(),
                'collaboration_id' => $id,
            ]);

            return redirect()->route('user.collaboration.details', $hashId)->with('success', 'Kolaborasi berhasil diperbarui!');
        } catch (\Exception $e) {
            Log::error('Failed to update collaboration: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'collaboration_id' => $id,
            ]);
            return redirect()->back()->withErrors(['error' => 'Gagal memperbarui kolaborasi']);
        }
    }

    public function destroy($hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            abort(404, 'Kolaborasi tidak ditemukan');
        }

        $id = $decoded[0];
        $collaboration = Collaboration::findOrFail($id);

        if ($collaboration->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        try {
            if ($collaboration->image) {
                Storage::disk('public')->delete($collaboration->image);
            }
            $collaboration->delete();
            return redirect()->route('user.home')->with('success', 'Kolaborasi berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal menghapus kolaborasi: ' . $e->getMessage()]);
        }
    }

    public function toggleInterest($hashId)
    {
        $decoded = \Vinkla\Hashids\Facades\Hashids::decode($hashId);

        if (empty($decoded)) {
            return response()->json(['error' => 'Kolaborasi tidak ditemukan'], 404);
        }

        $id = $decoded[0];
        $collaboration = Collaboration::findOrFail($id);
        $userId = Auth::id();

        $existingInterest = $collaboration->interests()->where('user_id', $userId)->first();

        if ($existingInterest) {
            $existingInterest->delete();
            $isInterested = false;
        } else {
            $collaboration->interests()->create(['user_id' => $userId]);
            $isInterested = true;
        }

        return response()->json([
            'isInterested' => $isInterested,
            'interestsCount' => $collaboration->interests()->count(),
        ]);
    }
}
