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
    public function index()
    {

        return Inertia::render('user/Event', [
            'data' => EventModel::with('user')->get()
        ]);
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
            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('images', 'public');
            }
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
            if ($data->user_id == Auth::id()) {
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
