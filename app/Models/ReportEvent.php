<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportEvent extends Model
{
    protected $table = 'report_event';
    protected $fillable = ['event_id', 'reason'];

    public function event()
    {
        return $this->belongsTo(EventModel::class, 'event_id');
    }
}
