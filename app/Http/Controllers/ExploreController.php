<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExploreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = User::query();

        // Always exclude admin users from explore
        $query->where('role', '!=', 'admin');

        if ($search) {
            // When searching, show only user, moderator, and community roles
            $query->where('name', 'like', '%' . $search . '%')
                ->whereIn('role', ['user', 'moderator', 'community']);
        } else {
            // When not searching, show only community users
            $query->where('role', 'community');
        }

        $users = $query->select('id', 'name', 'role')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('user/Explore', [
            'users'   => $users,
            'filters' => $request->only('search'),
        ]);
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
        //
    }
}
