<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\EventModel;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
            // Free event creation for community, admin, and moderator
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('images', 'public');
            }
            $data['is_visible'] = true;
            EventModel::create($data);
            return redirect()->route('user.event')->with('success', 'Event telah dibuat');
        } catch (Exception $e) {
            return redirect()->back()->with('failed', 'Event gagal dibuat');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $hashId)
    {
        $decoded = Hashids::decode($hashId);

        $id = $decoded[0];
        $event = EventModel::with('user')->findOrFail($id);

        // Check if event is invisible
        if (!$event->is_visible) {
            // Only allow owner to view invisible events
            if (!Auth::check() || Auth::id() !== $event->user_id) {
                abort(404, 'Event not found');
            }
        }

        return Inertia::render('user/DetailEvent', [
            'event' => $event,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * Also deletes associated pending transaction if event is invisible
     */
    public function destroy(string $id)
    {
        try {
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
}
