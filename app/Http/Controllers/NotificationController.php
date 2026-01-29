<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Add related data for routing
        $notifications->getCollection()->transform(function ($notification) {
            if ($notification->type === 'event' && $notification->post_id) {
                $event = \App\Models\EventModel::find($notification->post_id);
                if ($event) {
                    $notification->event_title = $event->title;
                }
            } elseif ($notification->type === 'collaboration' && $notification->post_id) {
                $collab = \App\Models\Collaboration::find($notification->post_id);
                if ($collab) {
                    $notification->collab_hash_id = $collab->hash_id;
                }
            }
            return $notification;
        });

        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
        // dd($notifications->toArray());

        return Inertia::render('user/Notification', [
            'notification' => $notifications->toArray(),
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Get paginated notifications for infinite scroll
     */
    public function getNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Add related data for routing
        $notifications->getCollection()->transform(function ($notification) {
            if ($notification->type === 'event' && $notification->post_id) {
                $event = \App\Models\EventModel::find($notification->post_id);
                if ($event) {
                    $notification->event_title = $event->title;
                }
            } elseif ($notification->type === 'collaboration' && $notification->post_id) {
                $collab = \App\Models\Collaboration::find($notification->post_id);
                if ($collab) {
                    $notification->collab_hash_id = $collab->hash_id;
                }
            }
            return $notification;
        });

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }

        $notification->update(['is_read' => true]);


        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
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
    public function store(NotificationRequest $request) {}

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Notification $notification)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Notification $notification)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification)
    {
        //
    }
}
