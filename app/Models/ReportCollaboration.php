<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportCollaboration extends Model
{
    protected $table = 'report_collaboration';

    protected $fillable = [
        'collaboration_id',
        'reason',
    ];

    public function collaboration()
    {
        return $this->belongsTo(\App\Models\Collaboration::class, 'collaboration_id');
    }
}
