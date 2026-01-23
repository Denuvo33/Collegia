<?php

namespace App\Http\Controllers;

use App\Helpers\SecurityHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\EventModel;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString(); // Get today's date only (YYYY-MM-DD)
        $filter = $request->query('filter', 'terbaru');
        $search = $request->query('search', '');

        $query = EventModel::with('user')
            ->select('*')
            ->selectRaw("
            CASE 
                WHEN promoted_till IS NOT NULL 
                     AND DATE(promoted_till) >= ? 
                THEN 1 
                ELSE 0 
            END AS is_promoted
        ", [$today])
            ->selectRaw("
            CASE 
                WHEN promoted_till IS NOT NULL 
                     AND DATE(promoted_till) >= ? 
                THEN promoted_till
                ELSE NULL
            END AS sort_promoted_till
        ", [$today])
            ->withCount('interests')
            ->where('is_visible', true);

        // Apply global search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('organizer', 'like', '%' . $search . '%');
            });
        }

        // Apply filter-specific logic
        switch ($filter) {
            case 'terlama':
                $query->orderByDesc('is_promoted')
                    ->orderByDesc('sort_promoted_till')
                    ->orderBy('created_at', 'asc');
                break;

            case 'dalam_waktu_dekat':
                $query->whereDate('date_event', '>=', $today)
                    ->orderByDesc('is_promoted')
                    ->orderByDesc('sort_promoted_till')
                    ->orderBy('date_event', 'asc');
                break;

            case 'berlangsung':
                $query->whereDate('date_event', '=', $today)
                    ->orderByDesc('is_promoted')
                    ->orderByDesc('sort_promoted_till')
                    ->orderByDesc('created_at');
                break;

            case 'terbaru':
            default:
                $query->orderByDesc('is_promoted')
                    ->orderByDesc('sort_promoted_till')
                    ->orderByDesc('created_at');
                break;
        }


        $events = $query->paginate(10)->toArray();

        return Inertia::render('user/Event', [
            'data' => $events,
            'currentFilter' => $filter,
            'currentSearch' => $search,
        ]);
    }



    public function promote(Request $request, $eventId)
    {
        try {
            $event = EventModel::findOrFail($eventId);
            if (Auth::id() != $event->user_id && $event->promoted_till != null) {
                return redirect()->back()->with('failed', 'Anda tidak memiliki izin untuk mempromosikan event ini');
            }

            $request->validate([
                'promote_event' => 'required|date',
            ]);

            $event->promoted_till = $request->promote_event;
            $event->save();
            return redirect()->back()->with('success', 'Event telah dipromosikan');
        } catch (Exception $e) {
            return redirect()->back()->with('failed', 'Promosi event gagal');
        }
    }

    /**
     * Show promotion form for specific event
     */
    public function promoteForm($eventId)
    {
        try {
            $event = EventModel::findOrFail($eventId);

            // Only allow owner to promote
            if (Auth::id() != $event->user_id) {
                return redirect()->back()->with('failed', 'Anda tidak memiliki izin untuk mempromosikan event ini');
            }

            return Inertia::render('user/PromoteEvent', [
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'content' => $event->content,
                    'image' => $event->image,
                    'date_event' => $event->date_event,
                    'location' => $event->location,
                    'organizer' => $event->organizer,
                    'price' => $event->price,
                ],
            ]);
        } catch (Exception $e) {
            return redirect()->route('user.event')->with('failed', 'Event tidak ditemukan');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('user/CreateEvent', []);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EventRequest $request)
    {
        try {
            $data = $request->validated();

            // Sanitize text fields to prevent XSS
            $data['title'] = SecurityHelper::sanitizeHtml($data['title']);
            $data['content'] = SecurityHelper::sanitizeHtml($data['content']);
            $data['organizer'] = SecurityHelper::sanitizeHtml($data['organizer']);
            $data['location'] = SecurityHelper::sanitizeHtml($data['location']);

            // Check for spam content
            if (SecurityHelper::containsSpam($data['content']) || SecurityHelper::containsSpam($data['title'])) {
                return redirect()->back()
                    ->withErrors(['error' => 'Konten terdeteksi sebagai spam. Mohon gunakan kata-kata yang lebih sopan.'])
                    ->withInput();
            }

            // Check for duplicate content (within last 24 hours)
            if (SecurityHelper::isDuplicate($data['content'], 'event', Auth::id(), 24)) {
                return redirect()->back()
                    ->withErrors(['error' => 'Event dengan konten yang sama sudah dibuat dalam 24 jam terakhir.'])
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
                    'events',
                    Str::uuid() . '.' . $request->image->extension(),
                    'r2'
                );
            }

            // Store content hash for duplicate detection
            $data['content_hash'] = SecurityHelper::getContentHash($data['content']);
            $data['is_visible'] = true;

            EventModel::create($data);

            Log::info('Event created successfully', [
                'user_id' => Auth::id(),
                'title' => $data['title'],
            ]);

            return redirect()->route('user.event')->with('success', 'Event telah dibuat');
        } catch (Exception $e) {
            Log::error('Failed to create event: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->withErrors(['error' => 'Event gagal dibuat. Silakan coba lagi.'])
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $hashId)
    {
        $decoded = Hashids::decode($hashId);

        $id = $decoded[0];
        $event = EventModel::with(['user', 'interestedUsers'])->withCount('interests')->findOrFail($id);

        // Check if event is invisible
        if (!$event->is_visible) {
            // Only allow owner to view invisible events
            if (!Auth::check() || Auth::id() !== $event->user_id) {
                abort(404, 'Event not found');
            }
        }

        $isInterested = Auth::check() ? $event->interestedUsers->contains(Auth::id()) : false;

        return Inertia::render('user/DetailEvent', [
            'event' => $event,
            'isInterested' => $isInterested,
        ]);
    }


    /**
     * Remove the specified resource from storage.
     * Also deletes associated pending transaction if event is invisible
     */
    public function destroy(string $hashId)
    {
        try {
            // Decode hashId to get real ID
            $decoded = Hashids::decode($hashId);

            if (empty($decoded)) {
                return redirect()->back()->with('failed', 'Event tidak ditemukan');
            }

            $id = $decoded[0];

            DB::beginTransaction();

            $event = EventModel::findOrFail($id);

            // Check permissions
            if ($event->user_id != Auth::id() && Auth::user()->role != 'admin' && Auth::user()->role != 'moderator') {
                return redirect()->back()->with('failed', 'Anda tidak memiliki izin untuk menghapus event ini');
            }

            $deletedTransactionId = null;

            // If event is invisible, delete associated pending transaction
            if (!$event->is_visible) {
                $transaction = Transaction::where('event_id', $event->id)
                    ->where('transaction_status', Transaction::STATUS_PENDING)
                    ->first();

                if ($transaction) {
                    $deletedTransactionId = $transaction->order_id;
                    $transaction->delete();

                    Log::info('Pending transaction deleted along with invisible event', [
                        'transaction_id' => $deletedTransactionId,
                        'event_id' => $event->id,
                        'event_title' => $event->title,
                        'user_id' => Auth::id(),
                    ]);
                }
            }

            // Delete event image from R2 if exists
            if ($event->image) {
                Storage::disk('r2')->delete($event->image);
            }

            // Delete event
            $event->delete();

            DB::commit();

            Log::info('Event deleted', [
                'event_id' => $id,
                'was_visible' => $event->is_visible,
                'deleted_transaction' => $deletedTransactionId,
                'user_id' => Auth::id(),
            ]);

            return redirect()->back()->with('success', 'Event telah dihapus');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete event: ' . $e->getMessage(), [
                'event_id' => $id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->with('failed', 'Event gagal dihapus');
        }
    }

    /**
     * Toggle user's interest in an event
     */
    public function toggleInterest($hashId)
    {
        $decoded = Hashids::decode($hashId);

        if (empty($decoded)) {
            return response()->json(['error' => 'Event tidak ditemukan'], 404);
        }

        $id = $decoded[0];
        $event = EventModel::findOrFail($id);
        $userId = Auth::id();

        $existingInterest = $event->interests()->where('user_id', $userId)->first();

        if ($existingInterest) {
            $existingInterest->delete();
            $isInterested = false;
        } else {
            $event->interests()->create(['user_id' => $userId]);
            $isInterested = true;
        }

        return response()->json([
            'isInterested' => $isInterested,
            'interestsCount' => $event->interests()->count(),
        ]);
    }
}
