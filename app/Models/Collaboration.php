<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    protected $appends = ['hash_id'];

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
}
