<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\CommentModel;

class Post extends Model
{
    protected $table = 'post';

    protected $fillable = [
        'user_id',
        'content',
        'image',
    ];

     public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function comments()
    {
        return $this->hasMany(CommentModel::class, 'post_id');
    }
}
