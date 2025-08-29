<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ReportModel extends Model
{
    protected $table = 'report_post';
    protected $fillable = ['post_id', 'reason'];


    public function posts()
    {
        return $this->hasMany(Post::class, 'post_id');
    }
}
