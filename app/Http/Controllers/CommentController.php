<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentRequest;
use App\Models\CommentModel;
use App\Models\Notification;
use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function store(CommentRequest $request)
    {
        try{
            $data = $request->validated();
            CommentModel::create($data);

            $post = Post::findOrFail($data['post_id']);

            Notification::create([
                'message'=> Auth::user()->name . ' commented on your post: '.$request->content,
                'post_id'=> $post->id,
                'user_id'=> $post->user_id,
                'is_read'=> 0,
            ]);
        }catch(Exception $e){
          
        }
      
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
    try {
        $comment = CommentModel::findOrFail($id);

        if ($comment->user_id !== Auth::id()) {
            return redirect()->back()->withErrors([
                'error' => 'Anda tidak memiliki izin untuk menghapus komentar ini.'
            ]);
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Komentar berhasil dihapus.');
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return redirect()->back()->withErrors([
            'message' => 'Komentar tidak ditemukan.'
        ]);
    } catch (\Exception $e) {

        return redirect()->back()->withErrors([
            'error' => 'Terjadi kesalahan, coba lagi nanti.'
        ]);
    }
}

}
