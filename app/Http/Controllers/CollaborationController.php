<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Collaboration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:collaboration,volunteer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'location' => 'nullable|string|max:255',
            'participants_needed' => 'nullable|integer|min:1',
        ]);

        try {
            $data = $request->all();
            $data['user_id'] = Auth::id();

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('collaborations', 'public');
            }

            Collaboration::create($data);
            return redirect()->route('user.home')->with('success', 'Kolaborasi berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal membuat kolaborasi: ' . $e->getMessage()]);
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

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:collaboration,volunteer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'location' => 'nullable|string|max:255',
            'participants_needed' => 'nullable|integer|min:1',
        ]);

        try {
            $data = $request->except(['image', '_method']);

            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($collaboration->image) {
                    Storage::disk('public')->delete($collaboration->image);
                }
                $data['image'] = $request->file('image')->store('collaborations', 'public');
            }

            $collaboration->update($data);
            return redirect()->route('user.collaboration.details', $hashId)->with('success', 'Kolaborasi berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal memperbarui kolaborasi: ' . $e->getMessage()]);
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
