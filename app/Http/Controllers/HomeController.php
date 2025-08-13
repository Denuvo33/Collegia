<?php

namespace App\Http\Controllers;

use App\Models\Home;
use App\Http\Controllers\Controller;
use App\Http\Requests\HomeRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Post::with('user')->get();
        return Inertia::render('user/Home',[
            'data'=> $data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HomeRequest $request)
    {
        try{
        $validated = $request->validated();
        Post::create($validated);

        return redirect()->route('user.home')
            ->with('success', 'Post Berhasil dibuat!');
        }catch(\Exception $e){
            return redirect()->back()->with('error', 'Gagal membuat post: ' . $e->getMessage());
        }
       
    }

    /**
     * Display the specified resource.
     */
    public function show(HomeRequest $home)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HomeRequest $home)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HomeRequest $home)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HomeRequest $home)
    {
        //
    }
}
