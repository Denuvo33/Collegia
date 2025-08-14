<?php

namespace App\Models;

use App\Models\Post as ModelsPost;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CommentModel extends Model
{
    protected $table = 'comment';

    protected $fillable = [
        'content',
        'user_id',
        'post_id',
    ];

    function post(){
        return $this->belongsTo(ModelsPost::class,'post_id');
    }

    function user(){
        return $this->belongsTo(User::class,'user_id');
    }
}
