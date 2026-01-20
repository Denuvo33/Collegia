<?php

namespace App\Models;

use App\Models\Post as ModelsPost;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Vinkla\Hashids\Facades\Hashids;

class CommentModel extends Model
{
    protected $table = 'comment';

    protected $fillable = [
        'content',
        'user_id',
        'post_id',
        'comment_hash',
    ];

    protected $appends = ['hash_id'];

    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id); // Fixed: was encoding post_id instead of id
    }

    function post()
    {
        return $this->belongsTo(ModelsPost::class, 'post_id');
    }

    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
