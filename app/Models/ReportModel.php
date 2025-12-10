<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ReportModel extends Model
{
    protected $table = 'report_post';
    protected $fillable = ['post_id', 'reason'];


    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }
}
