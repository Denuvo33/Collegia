<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class Collaboration extends Model
{
    protected $table = 'collaborations';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'start_date',
        'end_date',
        'image',
        'location',
        'participants_needed',
    ];

    protected $appends = ['hash_id', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function interests()
    {
        return $this->hasMany(CollaborationInterest::class, 'collaboration_id');
    }

    public function interestedUsers()
    {
        return $this->belongsToMany(User::class, 'collaboration_interests', 'collaboration_id', 'user_id')->withTimestamps();
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
