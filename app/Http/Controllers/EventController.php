<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\EventModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Vinkla\Hashids\Facades\Hashids;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $today = now();
        $filter = $request->query('filter', 'terbaru');

        $query = EventModel::with('user')
            ->select('*')
            ->selectRaw("
            CASE 
                WHEN promoted_till IS NOT NULL 
                     AND promoted_till >= ? 
                THEN 1 
                ELSE 0 
            END AS is_promoted
        ", [$today])
            ->selectRaw("
            CASE 
                WHEN promoted_till IS NOT NULL 
                     AND promoted_till >= ? 
                THEN promoted_till
                ELSE NULL
            END AS sort_promoted_till
        ", [$today])
            ->where('is_visible', true);

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
            $user = Auth::user();
            $data = $request->validated();

            // Check if user role allows free event creation
            if (in_array($user->role, ['community', 'admin', 'moderator'])) {
                // Free event creation for community, admin, and moderator
                if ($request->hasFile('image')) {
                    $data['image'] = $request->file('image')->store('images', 'public');
                }
                $data['is_visible'] = true;
                EventModel::create($data);
                return redirect()->route('user.event')->with('success', 'Event telah dibuat');
            } else {
                // Regular users need to pay - redirect  to frontend will handle payment initiation
                return redirect()->back()->with('error', 'Pengguna biasa perlu membayar untuk membuat event. Silakan gunakan tombol Buat Event dengan pembayaran.');
            }
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
     */
    public function destroy(string $id)
    {
        try {
            $data = EventModel::findOrFail($id);
            if ($data->user_id == Auth::id() || Auth::user()->role == 'admin' || Auth::user()->role == 'moderator') {
                $data->delete();
                return redirect()->back()->with('success', 'Event telah dihapus');
            } else {
                return redirect()->back()->with('failed', 'Anda tidak memiliki izin untuk menghapus event ini');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('failed', 'Event gagal dihapus');
        }
    }
}
