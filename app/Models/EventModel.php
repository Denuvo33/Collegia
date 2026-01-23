<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class EventModel extends Model
{
    protected $table = 'event';

    protected $fillable = [
        'user_id',
        'organizer',
        'date_event',
        'image',
        'title',
        'content',
        'price',
        'location',
        'status',
        'promoted_till',
        'is_visible',
        'user_pay_transaction_id',
        'content_hash',
    ];

    protected $appends = ['hash_id', 'image_url'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'user_pay_transaction_id');
    }

    public function interests()
    {
        return $this->hasMany(EventInterest::class, 'event_id');
    }

    public function interestedUsers()
    {
        return $this->belongsToMany(User::class, 'event_interests', 'event_id', 'user_id')->withTimestamps();
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
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
