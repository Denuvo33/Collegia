<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\CommentModel;
use Vinkla\Hashids\Facades\Hashids;

class Post extends Model
{
    protected $table = 'post';

    protected $fillable = [
        'user_id',
        'content',
        'image',
        'content_hash',
    ];
    protected $appends = ['hash_id', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    public function comments()
    {
        return $this->hasMany(CommentModel::class, 'post_id');
    }
    public function getHashIdAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // Check if image is already a full URL (for backwards compatibility)
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // Try R2 first, fallback to local storage
        if (Storage::disk('r2')->exists($this->image)) {
            return Storage::disk('r2')->url($this->image);
        }

        // Fallback to local public storage for old images
        return Storage::disk('public')->url($this->image);
    }
}
